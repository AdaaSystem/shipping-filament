<?php

declare(strict_types=1);

use Adaa\ShippingFilament\Resources\ShippingConnectionResource;
use Adaa\ShippingFilament\Resources\ShippingShipmentResource;
use Adaa\ShippingFilament\ShippingFilamentPlugin;
use Adaa\ShippingFilament\Support\CarrierOptions;
use Adaa\ShippingFilament\Support\MetaRedactor;
use Adaa\ShippingFilament\Tests\Support\Fixtures\TestMerchant;
use Adaa\ShippingFilament\Tests\Support\Fixtures\TestShippingConnection;
use Adaa\ShippingFilament\Tests\Support\Fixtures\TestShippingConnectionWithoutMerchant;
use Adaa\ShippingFilament\Tests\Support\Fixtures\TestShippingShipment;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config()->set('shipping-filament.connection_model', TestShippingConnection::class);
    config()->set('shipping-filament.shipment_model', TestShippingShipment::class);

    ShippingConnectionResource::merchantIdUsing(null);
    ShippingShipmentResource::merchantIdUsing(null);

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
        $table->string('origin_country', 2)->nullable();
        $table->string('external_account_id')->nullable();
        $table->timestamp('last_validated_at')->nullable();
        $table->timestamps();
    });

    Schema::create('test_shipping_connections_plain', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('merchant_id')->nullable();
        $table->string('carrier_code');
        $table->string('mode')->default('platform');
        $table->string('environment')->default('sandbox');
        $table->text('credentials')->nullable();
        $table->string('status')->default('active');
        $table->string('origin_country', 2)->nullable();
        $table->string('external_account_id')->nullable();
        $table->timestamp('last_validated_at')->nullable();
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

test('resources stay hidden until both host models are configured', function () {
    config()->set('shipping-filament.connection_model', null);
    config()->set('shipping-filament.shipment_model', null);

    expect(ShippingConnectionResource::shouldRegisterNavigation())->toBeFalse()
        ->and(ShippingShipmentResource::shouldRegisterNavigation())->toBeFalse()
        ->and(ShippingConnectionResource::isConfigured())->toBeFalse();
});

test('resources register navigation once both host models are configured', function () {
    expect(ShippingConnectionResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ShippingShipmentResource::shouldRegisterNavigation())->toBeTrue();
});

test('plugin registers no resources when unconfigured, both when configured', function () {
    config()->set('shipping-filament.connection_model', null);
    config()->set('shipping-filament.shipment_model', null);

    $emptyPanel = Panel::make();
    ShippingFilamentPlugin::make()->register($emptyPanel);

    expect($emptyPanel->getResources())->toBeEmpty();

    $panel = Panel::make();
    config()->set('shipping-filament.connection_model', TestShippingConnection::class);
    config()->set('shipping-filament.shipment_model', TestShippingShipment::class);

    ShippingFilamentPlugin::make()->register($panel);

    expect($panel->getResources())
        ->toContain(ShippingConnectionResource::class)
        ->toContain(ShippingShipmentResource::class);
});

test('connection form exposes credentials only on the create operation', function () {
    $fields = collect(ShippingConnectionResource::formComponents())
        ->mapWithKeys(fn ($field) => [$field->getName() => $field]);

    expect($fields->has('credentials'))->toBeTrue()
        ->and($fields->get('credentials'))->toBeInstanceOf(KeyValue::class)
        ->and(ShippingConnectionResource::credentialsVisibleOnOperations())->toBe(['create']);
});

test('merchant selector uses the relationship when available, else a numeric id', function () {
    config()->set('shipping-filament.connection_model', TestShippingConnection::class);

    expect(ShippingConnectionResource::merchantFormField())->toBeInstanceOf(Select::class);

    config()->set('shipping-filament.connection_model', TestShippingConnectionWithoutMerchant::class);

    expect(ShippingConnectionResource::merchantFormField())->toBeInstanceOf(TextInput::class);
});

test('connection table never exposes credential columns', function () {
    $names = collect(ShippingConnectionResource::tableColumns())
        ->map(fn ($column) => $column->getName())
        ->all();

    expect(implode(',', $names))->not->toContain('credential')
        ->and($names)->toContain('carrier_code', 'mode', 'environment', 'status');
});

test('connection infolist never exposes credentials', function () {
    $names = collect(ShippingConnectionResource::infolistComponents())
        ->map(fn ($entry) => $entry->getName())
        ->all();

    expect(implode(',', $names))->not->toContain('credential');
});

test('creating a connection round-trips credentials through the encrypted cast', function () {
    $connection = TestShippingConnection::create([
        'merchant_id' => null,
        'carrier_code' => 'bosta',
        'mode' => 'platform',
        'environment' => 'sandbox',
        'credentials' => ['api_key' => 'super-secret-value'],
        'status' => 'active',
    ]);

    expect($connection->credentials)->toBe(['api_key' => 'super-secret-value']);

    $raw = DB::table('test_shipping_connections')->where('id', $connection->id)->value('credentials');

    expect($raw)->not->toContain('super-secret-value')
        ->and(TestShippingConnection::find($connection->id)->credentials)->toBe(['api_key' => 'super-secret-value']);
});

test('shipment meta is redacted for display', function () {
    $redacted = MetaRedactor::redact([
        'api_key' => 'abc123',
        'PASSWORD' => 'hunter2',
        'city' => 'Cairo',
        'nested' => ['auth_token' => 'tok', 'weight' => 2.5],
    ]);

    expect($redacted['api_key'])->toBe(MetaRedactor::REDACTED)
        ->and($redacted['PASSWORD'])->toBe(MetaRedactor::REDACTED)
        ->and($redacted['city'])->toBe('Cairo')
        ->and($redacted['nested']['auth_token'])->toBe(MetaRedactor::REDACTED)
        ->and($redacted['nested']['weight'])->toBe(2.5);
});

test('meta redaction flags pass secret token and key fragments case-insensitively', function () {
    expect(MetaRedactor::isSensitiveKey('password'))->toBeTrue()
        ->and(MetaRedactor::isSensitiveKey('client_SECRET'))->toBeTrue()
        ->and(MetaRedactor::isSensitiveKey('Access-Token'))->toBeTrue()
        ->and(MetaRedactor::isSensitiveKey('api_key'))->toBeTrue()
        ->and(MetaRedactor::isSensitiveKey('city'))->toBeFalse()
        ->and(MetaRedactor::isSensitiveKey('carrier'))->toBeFalse()
        ->and(MetaRedactor::isSensitiveKey('tracking_number'))->toBeFalse();
});

test('shipment resource is read-only while connections keep full pages', function () {
    expect(array_keys(ShippingShipmentResource::getPages()))->toBe(['index', 'view'])
        ->and(array_keys(ShippingConnectionResource::getPages()))->toBe(['index', 'create', 'view', 'edit']);
});

test('shipment filters cover status and carrier', function () {
    $names = collect(ShippingShipmentResource::tableFilters())
        ->map(fn ($filter) => $filter->getName())
        ->all();

    expect($names)->toContain('status', 'carrier_code');
});

test('connection queries scope to the current merchant when resolvable', function () {
    $first = TestMerchant::create(['name' => 'First']);
    $second = TestMerchant::create(['name' => 'Second']);

    $owned = TestShippingConnection::create([
        'merchant_id' => $first->id, 'carrier_code' => 'bosta', 'credentials' => ['api_key' => 'one'],
    ]);
    TestShippingConnection::create([
        'merchant_id' => $second->id, 'carrier_code' => 'aramex', 'credentials' => ['api_key' => 'two'],
    ]);

    ShippingConnectionResource::merchantIdUsing($first->id);

    expect(ShippingConnectionResource::getEloquentQuery()->pluck('id')->all())->toBe([$owned->id]);

    ShippingConnectionResource::merchantIdUsing(null);

    expect(ShippingConnectionResource::getEloquentQuery()->count())->toBe(2);
});

test('carrier options mirror the core adaa shipping registry', function () {
    $core = require __DIR__.'/../../vendor/adaa/shipping/config/carriers.php';

    expect(CarrierOptions::codes())->toMatchArray(array_keys($core))
        ->and(CarrierOptions::label('bosta'))->toBe('Bosta');
});
