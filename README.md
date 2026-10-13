# Adaa Shipping Filament

> **English** | [العربية](README.ar.md)

Admin UI for [`adaa/shipping`](https://github.com/AdaaSystem/shipping): manage carrier connections, browse shipments, and watch a shipping dashboard — inside your Filament panel. The core library works without it.

```bash
composer require adaa/shipping adaa/shipping-filament
php artisan vendor:publish --tag=shipping-filament-config
```

## 5-minute setup

**1. Point the package at your models.** The core ships no Eloquent models, so tell it yours (or set the `.env` vars):

```php
// config/shipping-filament.php
'connection_model' => \App\Models\ShippingConnection::class,
'shipment_model' => \App\Models\ShippingShipment::class,
```

```env
SHIPPING_CONNECTION_MODEL=App\Models\ShippingConnection
SHIPPING_SHIPMENT_MODEL=App\Models\ShippingShipment
```

Your models need these attributes (anything extra is ignored):

- Connection: `merchant_id?`, `carrier_code`, `mode` (`platform`/`merchant`), `environment` (`sandbox`/`production`), `credentials` (cast `encrypted:array`, add to `$hidden`), `status`, `origin_country?`, `external_account_id?`, `last_validated_at?`
- Shipment: `order_id?`, `shipping_connection_id?`, `carrier_code`, `tracking_number?`, `status`, `rate_amount?`, `rate_currency?`, `finalized_at?`, `meta?` (array cast)

Minimum viable models (copy, rename the namespace, done):

```php
class ShippingConnection extends Model
{
    protected $fillable = ['merchant_id', 'carrier_code', 'mode', 'environment',
        'credentials', 'status', 'origin_country', 'external_account_id'];
    protected $hidden = ['credentials'];
    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array', 'last_validated_at' => 'datetime'];
    }
    public function merchant(): BelongsTo { return $this->belongsTo(User::class); }
    public function shipments(): HasMany { return $this->hasMany(ShippingShipment::class); }
}

class ShippingShipment extends Model
{
    protected $fillable = ['order_id', 'shipping_connection_id', 'carrier_code',
        'tracking_number', 'status', 'rate_amount', 'rate_currency', 'meta'];
    protected function casts(): array
    {
        return ['meta' => 'array', 'finalized_at' => 'datetime'];
    }
}
```

**2. Register the plugin.** Resources, dashboard page, and widgets appear automatically — and stay hidden until both models are configured:

```php
use Adaa\ShippingFilament\ShippingFilamentPlugin;

$panel->plugin(ShippingFilamentPlugin::make());
```

**3. Scope merchants (multi-vendor panels).** Without this, every merchant sees every row:

```php
use Adaa\ShippingFilament\Resources\BaseShippingResource;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource;
use Adaa\ShippingFilament\Resources\ShippingShipmentResource;

// Who is "the current merchant"? Static id, closure, or Filament tenant (when tenancy_aware).
ShippingConnectionResource::merchantIdUsing(fn () => auth('merchant')->id());

// Default rule already covers the common case: rows with your merchant_id
// plus platform rows (merchant_id NULL) stay visible; anything else hides.
// Override per model only when your ownership differs:
BaseShippingResource::scopeUsing(ShippingShipment::class,
    fn ($query) => $query->whereHas('order.store',
        fn ($stores) => $stores->where('merchant_id', auth('merchant')->id())));
```

## What you get

**Connections** (`Shipping connections` nav) — create platform or merchant accounts. The form shows exactly the credential fields each carrier needs (Bosta: `api_key`; Aramex: username/password/account…), as password inputs, only on create — never again. Extras (`base_url`, `timeout`) go in the additional-credentials box and merge on save.

**Validate button** (View/Edit page) — checks the required keys, then proves a Bosta key with a live `cities()` lookup (other carriers: presence check, stated honestly in the notice). Success writes `active` + timestamp; failure writes `invalid`. Secrets never appear in messages.

**Capabilities inspector** — per-connection modal listing what the carrier supports, from registry metadata, no secrets touched.

**Shipments** (read-only) — tracking numbers (click-to-copy), status badges, carrier/status filters, credential-scrubbed metadata, rate display.

**Dashboard page** — totals, delivery rate, in-transit/failed counts, active connections, per-status and per-carrier charts, latest shipments, plus a shipments relation on each connection and nav badges. Everything above respects merchant scoping.

## Calling it from your code

```php
use Adaa\ShippingFilament\Resources\ShippingConnectionResource;
use Adaa\ShippingFilament\Support\CarrierOptions;
use Adaa\ShippingFilament\Support\ConnectionValidator;
use Adaa\ShippingFilament\Support\MetaRedactor;
use Adaa\ShippingFilament\Support\ShippingDashboardData;

// Carrier select options + labels for your own forms:
CarrierOptions::options();          // ['bosta' => 'Bosta', ...]
CarrierOptions::label('bosta');     // 'Bosta'

// Validate credentials without Filament (e.g. in an API controller):
$missing = ConnectionValidator::missingFor('bosta', $request->input('credentials', []));
if ($missing !== []) {
    abort(422, 'Missing credentials: '.implode(', ', $missing));
}

// Scrub any array before display/logging (masks *pass*/*secret*/*token*/*key*, nested too):
$safe = MetaRedactor::redact($shipment->meta ?? []);

// Dashboard numbers for your own widgets (merchant-aware):
$stats = ShippingDashboardData::stats(ShippingShipment::class, ShippingConnection::class);
// ['shipments', 'delivered', 'delivered_rate', 'in_transit', 'failed', 'active_connections']
```

## Security model

- Credentials are encrypted by **your** model cast and never rendered (tables, infolists, edit forms, notifications, logs).
- Validation messages name missing **keys**, never values.
- Merchant isolation is server-side (`merchantIdUsing` + `scopeUsing`); the client never supplies the merchant id.

## Requirements

PHP `^8.3`, `filament/filament ^5.0`, `adaa/shipping dev-main` (stable `^0.1` constraint follows once Packagist syncs the core tags).

## Contributing

MIT. Test for every change (`composer test`), Pint before PRs.
