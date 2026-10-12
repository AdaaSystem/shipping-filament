<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Support;

/**
 * Credential-free connection inspection: what a carrier connection CAN do,
 * answered from static registry metadata only — never by touching secrets.
 */
final class ConnectionInspector
{
    /**
     * Declared capabilities for a carrier code, merged from the core
     * adaa/shipping registry when available, else the local carrier map.
     *
     * @return array<string, bool>
     */
    public static function capabilitiesFor(string $carrierCode): array
    {
        /** @var array<string, mixed> $registry */
        $registry = config('shipping.carriers', []);

        if ($registry === []) {
            $registry = self::coreRegistryDefaults();
        }

        $entry = $registry[$carrierCode] ?? null;

        if (is_array($entry) && isset($entry['capabilities']) && is_array($entry['capabilities'])) {
            /** @var array<string, bool> $capabilities */
            $capabilities = $entry['capabilities'];

            return $capabilities;
        }

        return [];
    }

    /**
     * Static core registry shipped inside adaa/shipping. Used when the host
     * app has not published the shipping config (e.g. this package's own
     * testbench) — metadata only, never credentials.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function coreRegistryDefaults(): array
    {
        $candidates = [
            // This package's own dev checkout (vendor/ is the package's own).
            dirname(__DIR__, 2).'/vendor/adaa/shipping/config/carriers.php',
            // A host Laravel app with both packages installed.
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

    public static function isKnownCarrier(string $carrierCode): bool
    {
        if (self::capabilitiesFor($carrierCode) !== []) {
            return true;
        }

        return in_array($carrierCode, CarrierOptions::codes(), true);
    }

    /**
     * Human-readable capability lines for the inspection modal.
     *
     * @return list<string>
     */
    public static function describe(string $carrierCode): array
    {
        $capabilities = self::capabilitiesFor($carrierCode);

        if ($capabilities === []) {
            return ["No capability metadata for [{$carrierCode}] — check the carrier code."];
        }

        $lines = [];

        foreach ($capabilities as $operation => $supported) {
            $label = (string) str((string) $operation)->replace('_', ' ')->title();
            $lines[] = ($supported ? '✓ ' : '✗ ').$label;
        }

        return $lines;
    }
}
