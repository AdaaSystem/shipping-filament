<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament;

use Adaa\ShippingFilament\Pages\ShippingDashboard;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource;
use Adaa\ShippingFilament\Resources\ShippingShipmentResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class ShippingFilamentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'shipping';
    }

    public function register(Panel $panel): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $resources = array_filter([
            class_exists(ShippingConnectionResource::class) ? ShippingConnectionResource::class : null,
            class_exists(ShippingShipmentResource::class) ? ShippingShipmentResource::class : null,
        ]);

        if ($resources !== []) {
            $panel->resources($resources);
        }

        $panel->pages([
            ShippingDashboard::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return new static;
    }

    protected function isConfigured(): bool
    {
        return is_string(config('shipping-filament.connection_model'))
            && is_string(config('shipping-filament.shipment_model'));
    }
}
