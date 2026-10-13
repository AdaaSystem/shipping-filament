<?php

declare(strict_types=1);

use Adaa\ShippingFilament\Pages\ShippingDashboard;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource;
use Adaa\ShippingFilament\Resources\ShippingShipmentResource;
use Adaa\ShippingFilament\Support\CredentialFields;

test('credential requirements mirror the core registry', function () {
    expect(CredentialFields::requiredFor('bosta'))->toBe(['api_key'])
        ->and(CredentialFields::requiredFor('egyptpost'))->toBe([])
        ->and(CredentialFields::requiredFor('nope'))->toBe([])
        ->and(CredentialFields::allRequiredByCarrier()['bosta'])->toBe(['api_key'])
        ->and(CredentialFields::labelFor('api_key'))->toBe('Api Key');
});

test('extra credentials merge into the stored array on save', function () {
    expect(CredentialFields::mergeExtra([
        'carrier_code' => 'bosta',
        'credentials' => ['api_key' => 'k'],
        'extra_credentials' => ['base_url' => 'https://x.test', 'timeout' => 30],
    ]))->toBe([
        'carrier_code' => 'bosta',
        'credentials' => ['api_key' => 'k', 'base_url' => 'https://x.test', 'timeout' => 30],
    ])->and(CredentialFields::mergeExtra(['carrier_code' => 'bosta']))->toBe([
        'carrier_code' => 'bosta',
    ]);
});

test('interface labels translate to arabic', function () {
    app()->setLocale('ar');

    try {
        expect(__('shipping-filament::connection.carrier'))->toBe('شركة الشحن')
            ->and(__('shipping-filament::dashboard.title'))->toBe('لوحة الشحن')
            ->and(ShippingDashboard::getNavigationLabel())->toBe('لوحة الشحن')
            ->and(ShippingConnectionResource::getNavigationLabel())->toBe('اتصالات الشحن')
            ->and(ShippingShipmentResource::getNavigationLabel())->toBe('شحنات التوصيل');
    } finally {
        app()->setLocale('en');
    }

    expect(__('shipping-filament::connection.carrier'))->toBe('Carrier');
});
