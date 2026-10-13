<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Support;

use Adaa\Shipping\Connection\ShippingConnectionContext;
use Adaa\Shipping\Drivers\Bosta\BostaClient;
use Adaa\Shipping\Drivers\Bosta\BostaDriver;
use Adaa\Shipping\Enums\ConnectionMode;
use Adaa\Shipping\Enums\Environment;
use Illuminate\Http\Client\Factory as HttpFactory;
use Throwable;

/**
 * Connection validation decisions for stored shipping connections.
 *
 * Two tiers, stated honestly everywhere the result is shown:
 * - presence check (offline): every canonical key in the core registry's
 *   `required_credentials` must hold a non-empty value. Accepted aliases
 *   (e.g. Bosta `token` for `api_key`, SMSA `pass_key` for `passkey`) and
 *   documented pre-issued-token escape hatches (Mylerz/FedEx/UPS) satisfy
 *   the check without network access.
 * - live check (Bosta only): an authenticated `cities()` lookup through a
 *   driver built from the record's decrypted credentials. Any other carrier
 *   gets the presence verdict only.
 *
 * Secret values never appear in results, messages, or logs — only key
 * names (which are not secrets) are reported as missing.
 */
final class ConnectionValidator
{
    /**
     * Accepted aliases per carrier: canonical key => accepted alternatives.
     *
     * Mirrors what each core *Client actually reads from its config array.
     *
     * @var array<string, array<string, list<string>>>
     */
    private const KEY_ALIASES = [
        'bosta' => [
            'api_key' => ['token'],
        ],
        'smsa' => [
            'passkey' => ['pass_key', 'passKey', 'token'],
        ],
        'aramex' => [
            'username' => ['user_name'],
            'account_country_code' => ['account_country'],
        ],
        'dhl' => [
            'username' => ['api_user', 'user_name'],
        ],
        'jtexpress' => [
            'customer_pwd' => ['password'],
        ],
    ];

    /**
     * Carriers whose client accepts a pre-issued bearer (`token` or
     * `access_token`) instead of the canonical credential pair. Matches
     * MylerzClient, FedexClient, and UpsClient behavior.
     *
     * @var list<string>
     */
    private const TOKEN_HATCH_CARRIERS = ['mylerz', 'fedex', 'ups'];

    /**
     * Pure presence check: which of the required keys hold no usable value.
     *
     * A value counts as present unless it is null, an empty/blank string,
     * or an empty array. No aliases, no network, no secrets in output.
     *
     * @param  list<string>  $required
     * @param  array<string, mixed>  $credentials
     * @return list<string>
     */
    public static function check(array $required, array $credentials): array
    {
        $missing = [];

        foreach ($required as $key) {
            $key = (string) $key;

            if (! self::hasValue($credentials, $key)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * Canonical required credential keys for a carrier code, from the core
     * adaa/shipping registry (host config first, shipped defaults fallback).
     *
     * @return list<string>
     */
    public static function requiredFor(string $carrierCode): array
    {
        $entry = self::registry()[$carrierCode] ?? null;

        if (! is_array($entry)) {
            return [];
        }

        $required = $entry['required_credentials'] ?? [];

        if (! is_array($required)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $required), fn (string $key): bool => $key !== ''));
    }

    /**
     * Alias-aware presence check for a carrier: canonical keys satisfied by
     * any accepted alias, plus the documented token escape hatches.
     *
     * @param  array<string, mixed>  $credentials
     * @return list<string>
     */
    public static function missingFor(string $carrierCode, array $credentials): array
    {
        $code = strtolower($carrierCode);

        if (in_array($code, self::TOKEN_HATCH_CARRIERS, true) && self::hasPreIssuedToken($credentials)) {
            return [];
        }

        $missing = [];

        foreach (self::requiredFor($carrierCode) as $key) {
            if (self::hasValue($credentials, $key)) {
                continue;
            }

            $satisfied = false;

            foreach (self::aliasesFor($code, $key) as $alias) {
                if (self::hasValue($credentials, $alias)) {
                    $satisfied = true;

                    break;
                }
            }

            if (! $satisfied) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * Full validation verdict: presence check always, live `cities()` probe
     * for Bosta only. Never throws, never leaks secret values.
     *
     * @param  array<string, mixed>  $credentials
     * @return array{valid: bool, missing: list<string>, live_checked: bool, error: ?string}
     */
    public static function validate(string $carrierCode, array $credentials, string $environment = 'sandbox', ?HttpFactory $http = null): array
    {
        $missing = self::missingFor($carrierCode, $credentials);

        if ($missing !== []) {
            return [
                'valid' => false,
                'missing' => $missing,
                'live_checked' => false,
                'error' => 'Missing credentials: '.implode(', ', $missing).'.',
            ];
        }

        if (strtolower($carrierCode) !== 'bosta') {
            return ['valid' => true, 'missing' => [], 'live_checked' => false, 'error' => null];
        }

        try {
            self::probeBosta($credentials, $environment, $http);
        } catch (Throwable $e) {
            $message = $e->getMessage();

            return [
                'valid' => false,
                'missing' => [],
                'live_checked' => true,
                'error' => $message !== '' ? $message : 'Bosta live validation failed.',
            ];
        }

        return ['valid' => true, 'missing' => [], 'live_checked' => true, 'error' => null];
    }

    /**
     * Live Bosta authentication probe: builds a real driver from the given
     * (decrypted) credentials and performs the light `cities()` lookup.
     * Any transport or auth failure surfaces as an exception whose message
     * carries no secret values (BostaClient never interpolates the key).
     *
     * @param  array<string, mixed>  $credentials
     */
    public static function probeBosta(array $credentials, string $environment = 'sandbox', ?HttpFactory $http = null): void
    {
        $http ??= app(HttpFactory::class);

        $env = Environment::tryFrom(strtolower($environment)) ?? Environment::Sandbox;

        $config = $credentials;
        $config['environment'] = $env->value;

        $connection = new ShippingConnectionContext(
            connectionId: 'validation',
            carrierCode: 'bosta',
            mode: ConnectionMode::Platform,
            environment: $env,
            credentials: $config,
        );

        // Built from the record's decrypted credentials — proves the wiring.
        // BostaDriver exposes no lookup endpoint, so the light cities() probe
        // below reuses the identical credential array through the client.
        BostaDriver::fromConnection($http, $connection);

        (new BostaClient($http, $config))->cities();
    }

    /**
     * @return list<string>
     */
    private static function aliasesFor(string $carrierCode, string $key): array
    {
        return self::KEY_ALIASES[$carrierCode][$key] ?? [];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private static function hasPreIssuedToken(array $credentials): bool
    {
        return self::hasValue($credentials, 'token') || self::hasValue($credentials, 'access_token');
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private static function hasValue(array $credentials, string $key): bool
    {
        if (! array_key_exists($key, $credentials)) {
            return false;
        }

        $value = $credentials[$key];

        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return true;
    }

    /**
     * Core carrier registry: host app config first, shipped file fallback
     * (same lookup as ConnectionInspector for testbench parity).
     *
     * @return array<string, array<string, mixed>>
     */
    private static function registry(): array
    {
        /** @var array<string, mixed> $configured */
        $configured = config('shipping.carriers', []);

        if ($configured !== []) {
            return $configured;
        }

        $candidates = [
            dirname(__DIR__, 2).'/vendor/adaa/shipping/config/carriers.php',
            base_path('vendor/adaa/shipping/config/carriers.php'),
        ];

        foreach ($candidates as $path) {
            if (! is_readable($path)) {
                continue;
            }

            $carriers = require $path;

            if (is_array($carriers) && $carriers !== []) {
                return $carriers;
            }
        }

        return [];
    }
}
