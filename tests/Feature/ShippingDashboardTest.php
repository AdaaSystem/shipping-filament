<?php

declare(strict_types=1);

use Adaa\Shipping\Enums\ShipmentStatus;
use Adaa\ShippingFilament\Pages\ShippingDashboard;
use Adaa\ShippingFilament\Resources\BaseShippingResource;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource\RelationManagers\ShipmentsRelationManager;
use Adaa\ShippingFilament\Resources\ShippingShipmentResource;
use Adaa\ShippingFilament\ShippingFilamentPlugin;
use Adaa\ShippingFilament\Support\ConnectionInspector;
use Adaa\ShippingFilament\Support\ShippingDashboardData;
use Adaa\ShippingFilament\Tests\Support\Fixtures\TestMerchant;
use Adaa\ShippingFilament\Tests\Support\Fixtures\TestShippingConnection;
use Adaa\ShippingFilament\Tests\Support\Fixtures\TestShippingShipment;
use Filament\Panel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config()->set('shipping-filament.connection_model', TestShippingConnection::class);
    config()->set('shipping-filament.shipment_model', TestShippingShipment::class);

    BaseShippingResource::merchantIdUsing(null);

    Schema::create('test_merchants', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('test_shipping_connections', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('merchant_id')->nullable();
        $table->string('carrier_code');
        $table->string('mode')->default('platform');
        $table->string('environment')->default('sandbox');
        $table->text('credentials')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('test_shipping_shipments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('order_id')->nullable();
        $table->unsignedBigInteger('shipping_connection_id')->nullable();
        $table->string('carrier_code');
        $table->string('tracking_number')->nullable();
        $table->string('status')->default('created');
        $table->decimal('rate_amount', 10, 2)->nullable();
        $table->string('rate_currency', 3)->nullable();
        $table->timestamp('finalized_at')->nullable();
        $table->json('meta')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    BaseShippingResource::merchantIdUsing(null);
});

function seedDashboardScenario(): void
{
    TestShippingConnection::create(['merchant_id' => null, 'carrier_code' => 'bosta', 'status' => 'active']);
    TestShippingConnection::create(['merchant_id' => null, 'carrier_code' => 'aramex', 'status' => 'inactive']);

    TestShippingShipment::create(['carrier_code' => 'bosta', 'tracking_number' => 'B1', 'status' => ShipmentStatus::Delivered->value]);
    TestShippingShipment::create(['carrier_code' => 'bosta', 'tracking_number' => 'B2', 'status' => ShipmentStatus::Delivered->value]);
    TestShippingShipment::create(['carrier_code' => 'aramex', 'tracking_number' => 'A1', 'status' => ShipmentStatus::InTransit->value]);
    TestShippingShipment::create(['carrier_code' => 'mylerz', 'tracking_number' => 'M1', 'status' => ShipmentStatus::Created->value]);
}

test('dashboard stats aggregate shipments and connections', function () {
    seedDashboardScenario();

    $stats = ShippingDashboardData::stats(TestShippingShipment::class, TestShippingConnection::class);

    expect($stats['shipments'])->toBe(4)
        ->and($stats['delivered'])->toBe(2)
        ->and($stats['delivered_rate'])->toBe(50.0)
        ->and($stats['in_transit'])->toBe(1)
        ->and($stats['failed'])->toBe(0)
        ->and($stats['active_connections'])->toBe(1);
});

test('dashboard stats are zero when nothing exists', function () {
    $stats = ShippingDashboardData::stats(TestShippingShipment::class, TestShippingConnection::class);

    expect($stats)->toBe([
        'shipments' => 0,
        'delivered' => 0,
        'delivered_rate' => 0.0,
        'in_transit' => 0,
        'failed' => 0,
        'active_connections' => 0,
    ]);
});

test('shipments by status is zero-filled for every known status', function () {
    seedDashboardScenario();

    $byStatus = ShippingDashboardData::shipmentsByStatus(TestShippingShipment::class);

    expect($byStatus)->toHaveCount(count(ShipmentStatus::cases()))
        ->and($byStatus[ShipmentStatus::Delivered->value])->toBe(2)
        ->and($byStatus[ShipmentStatus::InTransit->value])->toBe(1)
        ->and($byStatus[ShipmentStatus::Cancelled->value])->toBe(0);
});

test('shipments by carrier counts per carrier code', function () {
    seedDashboardScenario();

    expect(ShippingDashboardData::shipmentsByCarrier(TestShippingShipment::class))
        ->toMatchArray(['bosta' => 2, 'aramex' => 1, 'mylerz' => 1]);
});

test('recent shipments return latest first within the limit', function () {
    seedDashboardScenario();

    $recent = ShippingDashboardData::recentShipments(TestShippingShipment::class, 2);

    expect($recent->pluck('tracking_number')->all())->toBe(['M1', 'A1']);
});

test('dashboard aggregates scope to the current merchant', function () {
    $first = TestMerchant::create(['name' => 'First']);
    $second = TestMerchant::create(['name' => 'Second']);

    TestShippingConnection::create(['merchant_id' => $first->id, 'carrier_code' => 'bosta', 'status' => 'active']);
    TestShippingConnection::create(['merchant_id' => $second->id, 'carrier_code' => 'aramex', 'status' => 'active']);

    BaseShippingResource::merchantIdUsing($first->id);

    $stats = ShippingDashboardData::stats(TestShippingShipment::class, TestShippingConnection::class);

    expect($stats['active_connections'])->toBe(1);
});

test('dashboard page registers only when configured', function () {
    expect(ShippingDashboard::shouldRegisterNavigation())->toBeTrue()
        ->and(ShippingDashboard::canAccess())->toBeTrue();

    config()->set('shipping-filament.connection_model', null);
    config()->set('shipping-filament.shipment_model', null);

    expect(ShippingDashboard::shouldRegisterNavigation())->toBeFalse()
        ->and(ShippingDashboard::canAccess())->toBeFalse();
});

test('plugin registers the dashboard page alongside resources', function () {
    $panel = Panel::make();

    ShippingFilamentPlugin::make()->register($panel);

    expect($panel->getPages())->toContain(ShippingDashboard::class);
});

test('connection inspector describes capabilities without secrets', function () {
    $lines = ConnectionInspector::describe('bosta');

    expect(ConnectionInspector::isKnownCarrier('bosta'))->toBeTrue()
        ->and(implode("\n", $lines))->toContain('Create')
        ->and(implode(',', $lines))->not->toContain('api_key', 'secret');

    expect(ConnectionInspector::isKnownCarrier('nope'))->toBeFalse()
        ->and(ConnectionInspector::describe('nope')[0])->toContain('nope');
});

test('connection resource exposes a credential-free shipments relation', function () {
    expect(ShippingConnectionResource::getRelations())->toContain(ShipmentsRelationManager::class)
        ->and(ShipmentsRelationManager::getRelationshipName())->toBe('shipments');
});

test('navigation badges count active connections and in-transit shipments', function () {
    seedDashboardScenario();

    expect(ShippingConnectionResource::getNavigationBadge())->toBe('1')
        ->and(ShippingShipmentResource::getNavigationBadge())->toBe('1');

    config()->set('shipping-filament.connection_model', null);
    config()->set('shipping-filament.shipment_model', null);

    expect(ShippingConnectionResource::getNavigationBadge())->toBeNull()
        ->and(ShippingShipmentResource::getNavigationBadge())->toBeNull();
});

test('platform connections stay visible to merchants by default', function () {
    TestShippingConnection::create(['merchant_id' => null, 'carrier_code' => 'bosta', 'status' => 'active']);

    BaseShippingResource::merchantIdUsing(99);

    try {
        expect(TestShippingConnection::query()->count())->toBe(1)
            ->and(BaseShippingResource::scopeQueryFor(TestShippingConnection::class)->count())->toBe(1);
    } finally {
        BaseShippingResource::merchantIdUsing(null);
    }
});

test('host apps can override scoping per model', function () {
    TestShippingConnection::create(['merchant_id' => 1, 'carrier_code' => 'bosta', 'status' => 'active']);
    TestShippingConnection::create(['merchant_id' => 2, 'carrier_code' => 'aramex', 'status' => 'active']);

    BaseShippingResource::scopeUsing(TestShippingConnection::class, fn ($query) => $query->where('carrier_code', 'bosta'));

    try {
        expect(BaseShippingResource::scopeQueryFor(TestShippingConnection::class)->pluck('carrier_code')->all())->toBe(['bosta']);
    } finally {
        BaseShippingResource::scopeUsing(TestShippingConnection::class, null);
    }

    expect(BaseShippingResource::scopeQueryFor(TestShippingConnection::class)->count())->toBe(2);
});

test('failed stat counts exception and failed rows', function () {
    TestShippingShipment::create(['carrier_code' => 'bosta', 'tracking_number' => 'E1', 'status' => ShipmentStatus::Exception->value]);
    TestShippingShipment::create(['carrier_code' => 'bosta', 'tracking_number' => 'F1', 'status' => 'failed']);
    TestShippingShipment::create(['carrier_code' => 'bosta', 'tracking_number' => 'D1', 'status' => ShipmentStatus::Delivered->value]);

    $stats = ShippingDashboardData::stats(TestShippingShipment::class, TestShippingConnection::class);

    expect($stats['failed'])->toBe(2)->and($stats['shipments'])->toBe(3);
});
