<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Support;

/**
 * Per-carrier credential fields driven by the core registry's
 * required_credentials metadata. The form renders one password input
 * per required key of the selected carrier; anything extra goes
 * through the additional-credentials KeyValue and is merged on save.
 */
final class CredentialFields
{
    /**
     * Required credential keys for one carrier code.
     *
     * @return list<string>
     */
    public static function requiredFor(string $carrierCode): array
    {
        /** @var array<string, mixed> $registry */
        $registry = config('shipping.carriers', []);

        if ($registry === []) {
            $registry = self::coreRegistryDefaults();
        }

        $entry = $registry[$carrierCode] ?? null;

        if (! is_array($entry) || ! isset($entry['required_credentials']) || ! is_array($entry['required_credentials'])) {
            return [];
        }

        /** @var list<string> $keys */
        $keys = array_values(array_filter($entry['required_credentials'], 'is_string'));

        return $keys;
    }

    /**
     * @return array<string, list<string>> carrier code => required keys (non-empty only).
     */
    public static function allRequiredByCarrier(): array
    {
        /** @var array<string, mixed> $registry */
        $registry = config('shipping.carriers', []);

        if ($registry === []) {
            $registry = self::coreRegistryDefaults();
        }

        $map = [];

        foreach ($registry as $code => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $keys = isset($entry['required_credentials']) && is_array($entry['required_credentials'])
                ? array_values(array_filter($entry['required_credentials'], 'is_string'))
                : [];

            if ($keys !== []) {
                $map[(string) $code] = $keys;
            }
        }

        return $map;
    }

    /**
     * Merge the additional-credentials KeyValue into the stored
     * credentials array. Pure — unit-tested, called by the Create page.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mergeExtra(array $data): array
    {
        $extra = $data['extra_credentials'] ?? null;
        unset($data['extra_credentials']);

        if (! is_array($extra) || $extra === []) {
            return $data;
        }

        $credentials = $data['credentials'] ?? [];
        $data['credentials'] = array_merge(is_array($credentials) ? $credentials : [], $extra);

        return $data;
    }

    public static function labelFor(string $key): string
    {
        return (string) str($key)->replace('_', ' ')->title();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function coreRegistryDefaults(): array
    {
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
