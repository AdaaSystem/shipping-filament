<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Support;

/**
 * Hardcoded carrier code => label map mirroring adaa/shipping config/carriers.php.
 *
 * This package must not hard-depend on the host app's shipping config, so the
 * list is duplicated here. Keep it in sync with adaa/shipping upstream.
 *
 * @see vendor/adaa/shipping/config/carriers.php
 */
final class CarrierOptions
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'bosta' => 'Bosta',
            'mylerz' => 'Mylerz',
            'smsa' => 'SMSA Express',
            'jtexpress' => 'J&T Express',
            'aramex' => 'Aramex',
            'fedex' => 'FedEx',
            'dhl' => 'DHL Express',
            'ups' => 'UPS',
            'egyptpost' => 'Egypt Post',
            'fake' => 'Fake (tests)',
        ];
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(static::options());
    }

    public static function label(string $code): string
    {
        return static::options()[$code] ?? $code;
    }
}
