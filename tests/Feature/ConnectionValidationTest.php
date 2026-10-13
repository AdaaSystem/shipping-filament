<?php

declare(strict_types=1);

use Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages\CreateShippingConnection;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages\EditShippingConnection;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages\ViewShippingConnection;
use Adaa\ShippingFilament\Support\ConnectionValidator;
use Adaa\ShippingFilament\Support\ValidateConnection;
use Adaa\ShippingFilament\Tests\Support\Fixtures\TestShippingConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config()->set('shipping-filament.connection_model', TestShippingConnection::class);

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
});

function connectionHeaderActionNames(string $pageClass): array
{
    if (! method_exists($pageClass, 'getHeaderActions')) {
        return [];
    }

    $method = new ReflectionMethod($pageClass, 'getHeaderActions');
    $method->setAccessible(true);

    $page = (new ReflectionClass($pageClass))->newInstanceWithoutConstructor();

    return collect($method->invoke($page))->map(fn ($action) => $action->getName())->all();
}

test('check reports exactly the keys with no usable value', function () {
    expect(ConnectionValidator::check(['api_key'], []))->toBe(['api_key'])
        ->and(ConnectionValidator::check(['api_key'], ['api_key' => null]))->toBe(['api_key'])
        ->and(ConnectionValidator::check(['api_key'], ['api_key' => '']))->toBe(['api_key'])
        ->and(ConnectionValidator::check(['api_key'], ['api_key' => '   ']))->toBe(['api_key'])
        ->and(ConnectionValidator::check(['api_key'], ['api_key' => []]))->toBe(['api_key'])
        ->and(ConnectionValidator::check(['api_key'], ['api_key' => 'live-key']))->toBe([])
        ->and(ConnectionValidator::check(['a', 'b'], ['a' => 'x', 'b' => 'y', 'extra' => 'ignored']))->toBe([])
        ->and(ConnectionValidator::check(['a', 'b'], ['a' => 'x']))->toBe(['b']);
});

test('required credentials mirror the core registry per carrier', function () {
    expect(ConnectionValidator::requiredFor('bosta'))->toBe(['api_key'])
        ->and(ConnectionValidator::requiredFor('mylerz'))->toBe(['username', 'password'])
        ->and(ConnectionValidator::requiredFor('smsa'))->toBe(['passkey'])
        ->and(ConnectionValidator::requiredFor('jtexpress'))->toBe(['api_account', 'customer_code', 'private_key', 'customer_pwd'])
        ->and(ConnectionValidator::requiredFor('aramex'))->toBe(['username', 'password', 'account_number', 'account_pin', 'account_entity', 'account_country_code'])
        ->and(ConnectionValidator::requiredFor('fedex'))->toBe(['client_id', 'client_secret'])
        ->and(ConnectionValidator::requiredFor('dhl'))->toBe(['username', 'password', 'account_number'])
        ->and(ConnectionValidator::requiredFor('ups'))->toBe(['client_id', 'client_secret'])
        ->and(ConnectionValidator::requiredFor('egyptpost'))->toBe([])
        ->and(ConnectionValidator::requiredFor('fake'))->toBe([])
        ->and(ConnectionValidator::requiredFor('unknown-carrier'))->toBe([]);
});

test('missing check honors the aliases each core client actually reads', function () {
    expect(ConnectionValidator::missingFor('bosta', ['token' => 'abc']))->toBe([])
        ->and(ConnectionValidator::missingFor('smsa', ['pass_key' => 'abc']))->toBe([])
        ->and(ConnectionValidator::missingFor('smsa', ['token' => 'abc']))->toBe([])
        ->and(ConnectionValidator::missingFor('aramex', [
            'user_name' => 'u', 'password' => 'p', 'account_number' => 'n',
            'account_pin' => 'i', 'account_entity' => 'e', 'account_country' => 'EG',
        ]))->toBe([])
        ->and(ConnectionValidator::missingFor('dhl', ['api_user' => 'u', 'password' => 'p', 'account_number' => 'n']))->toBe([])
        ->and(ConnectionValidator::missingFor('jtexpress', [
            'api_account' => 'a', 'customer_code' => 'c', 'private_key' => 'k', 'password' => 'p',
        ]))->toBe([]);
});

test('missing check honors pre-issued token escape hatches', function () {
    expect(ConnectionValidator::missingFor('mylerz', ['token' => 'abc']))->toBe([])
        ->and(ConnectionValidator::missingFor('mylerz', ['access_token' => 'abc']))->toBe([])
        ->and(ConnectionValidator::missingFor('mylerz', ['username' => 'u']))->toBe(['password'])
        ->and(ConnectionValidator::missingFor('fedex', ['token' => 'abc']))->toBe([])
        ->and(ConnectionValidator::missingFor('ups', ['access_token' => 'abc']))->toBe([])
        ->and(ConnectionValidator::missingFor('fedex', ['client_id' => 'id']))->toBe(['client_secret']);
});

test('run marks presence failures invalid without touching the timestamp', function () {
    $connection = TestShippingConnection::create([
        'merchant_id' => null,
        'carrier_code' => 'smsa',
        'mode' => 'platform',
        'environment' => 'sandbox',
        'credentials' => ['other' => 'TOP-SECRET-XYZ'],
        'status' => 'active',
    ]);

    $result = ValidateConnection::run($connection);

    expect($result['valid'])->toBeFalse()
        ->and($result['missing'])->toBe(['passkey'])
        ->and($result['live_checked'])->toBeFalse()
        ->and($result['error'])->toContain('passkey')
        ->and($result['error'])->not->toContain('TOP-SECRET-XYZ');

    $connection->refresh();

    expect($connection->status)->toBe('invalid')
        ->and($connection->last_validated_at)->toBeNull();
});

test('run marks presence-only carriers valid with a timestamp', function () {
    $connection = TestShippingConnection::create([
        'merchant_id' => null,
        'carrier_code' => 'fake',
        'mode' => 'platform',
        'environment' => 'sandbox',
        'credentials' => [],
        'status' => 'inactive',
    ]);

    $result = ValidateConnection::run($connection);

    expect($result['valid'])->toBeTrue()
        ->and($result['live_checked'])->toBeFalse();

    $connection->refresh();

    expect($connection->status)->toBe('active')
        ->and($connection->last_validated_at)->not->toBeNull();
});

test('run fails bosta on missing keys before any network call', function () {
    $factory = new Factory;
    $factory->fake(['*' => Factory::response('boom', 500)]);

    $connection = TestShippingConnection::create([
        'merchant_id' => null,
        'carrier_code' => 'bosta',
        'mode' => 'platform',
        'environment' => 'sandbox',
        'credentials' => [],
        'status' => 'active',
    ]);

    $result = ValidateConnection::run($connection, $factory);

    expect($result['valid'])->toBeFalse()
        ->and($result['missing'])->toBe(['api_key'])
        ->and($result['live_checked'])->toBeFalse()
        ->and($connection->refresh()->status)->toBe('invalid');
});

test('run verifies bosta with a live cities lookup', function () {
    $factory = new Factory;
    $factory->fake(['*bosta.co*' => Factory::response(['success' => true, 'data' => ['Cairo']], 200)]);

    $connection = TestShippingConnection::create([
        'merchant_id' => null,
        'carrier_code' => 'bosta',
        'mode' => 'platform',
        'environment' => 'sandbox',
        'credentials' => ['api_key' => 'live-key-123'],
        'status' => 'inactive',
    ]);

    $result = ValidateConnection::run($connection, $factory);

    expect($result['valid'])->toBeTrue()
        ->and($result['live_checked'])->toBeTrue();

    $connection->refresh();

    expect($connection->status)->toBe('active')
        ->and($connection->last_validated_at)->not->toBeNull();
});

test('run marks bosta invalid when the live lookup rejects the key', function () {
    $factory = new Factory;
    $factory->fake(['*bosta.co*' => Factory::response(['success' => false, 'message' => 'Invalid API key.'], 401)]);

    $connection = TestShippingConnection::create([
        'merchant_id' => null,
        'carrier_code' => 'bosta',
        'mode' => 'platform',
        'environment' => 'sandbox',
        'credentials' => ['api_key' => 'live-key-123'],
        'status' => 'active',
    ]);

    $result = ValidateConnection::run($connection, $factory);

    expect($result['valid'])->toBeFalse()
        ->and($result['live_checked'])->toBeTrue()
        ->and($result['error'])->toContain('Invalid API key.')
        ->and($result['error'])->not->toContain('live-key-123');

    $connection->refresh();

    expect($connection->status)->toBe('invalid')
        ->and($connection->last_validated_at)->toBeNull();
});

test('validate connection action lives on view and edit pages only', function () {
    expect(ValidateConnection::make()->getName())->toBe('validateConnection')
        ->and(connectionHeaderActionNames(ViewShippingConnection::class))->toContain('validateConnection')
        ->and(connectionHeaderActionNames(EditShippingConnection::class))->toContain('validateConnection')
        ->and(connectionHeaderActionNames(CreateShippingConnection::class))->not->toContain('validateConnection');
});
