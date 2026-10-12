<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Support;

/**
 * Redacts credential-like values from shipment meta before display.
 *
 * Any array key containing pass/secret/token/key (case-insensitive) has its
 * value replaced. Never render raw meta in tables or infolists without this.
 */
final class MetaRedactor
{
    public const REDACTED = '••••••';

    /**
     * @var list<string>
     */
    private const SENSITIVE_FRAGMENTS = ['pass', 'secret', 'token', 'key'];

    public static function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_FRAGMENTS as $fragment) {
            if (str_contains(strtolower($key), $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public static function redact(array $meta): array
    {
        $redacted = [];

        foreach ($meta as $key => $value) {
            $key = (string) $key;

            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $redacted[$key] = self::isSensitiveKey($key) ? self::REDACTED : self::redact($value);

                continue;
            }

            $redacted[$key] = self::isSensitiveKey($key) ? self::REDACTED : $value;
        }

        return $redacted;
    }
}
