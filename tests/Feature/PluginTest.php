<?php

declare(strict_types=1);

use Adaa\ShippingFilament\ShippingFilamentPlugin;
use Filament\Contracts\Plugin;

test('plugin implements the filament plugin contract', function () {
    $plugin = ShippingFilamentPlugin::make();

    expect($plugin)->toBeInstanceOf(Plugin::class)
        ->and($plugin->getId())->toBe('shipping');
});

test('package config publishes with null host models by default', function () {
    expect(config('shipping-filament.connection_model'))->toBeNull()
        ->and(config('shipping-filament.shipment_model'))->toBeNull();
});
