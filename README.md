# Adaa Shipping Filament

> **English** | [العربية](README.ar.md)

Optional Filament admin UI for [`adaa/shipping`](https://github.com/AdaaSystem/shipping). The core library is complete without it — install this package only if you run a Filament panel.

## What it provides

- **Shipping connections management** — platform-owned and merchant-owned carrier accounts, with credentials editable **only on create** and never displayed in tables, infolists, or edit forms.
- **Shipments browser** — read-only tracking views with copyable tracking numbers, status badges, carrier filters, and credential-safe metadata display (`MetaRedactor` masks any `*pass*`, `*secret*`, `*token*`, `*key*` values, including nested ones).
- **Merchant scoping** — list queries are filtered by the current merchant whenever the host model exposes a `merchant()` relationship, so a merchant never sees another merchant's connections.
- **Zero-config hiding** — resources stay unregistered until both host models are configured.

## Requirements

- PHP `^8.3`, `filament/filament ^5.0`, `adaa/shipping` (any corresponding version).

## Installation

```bash
composer require adaa/shipping adaa/shipping-filament
php artisan vendor:publish --tag=shipping-filament-config
```

### Local development (sibling checkout)

CI and Packagist installs resolve `adaa/shipping` remotely. To develop against a local sibling checkout instead, add a **local-only** path repository (never committed):

```bash
composer config repositories.local '{"type": "path", "url": "../shipping", "options": {"symlink": true}}'
composer update adaa/shipping
```

## Configuration

Point the package at **your** Eloquent models (the core is framework-agnostic and ships none):

```php
// config/shipping-filament.php — or via .env
'connection_model' => \App\Models\ShippingConnection::class,
'shipment_model' => \App\Models\ShippingShipment::class,
```

```env
SHIPPING_CONNECTION_MODEL=App\Models\ShippingConnection
SHIPPING_SHIPMENT_MODEL=App\Models\ShippingShipment
```

### Host-model contract

**Connection model** — expected attributes: `merchant_id?`, `carrier_code`, `mode` (`platform|merchant`), `environment` (`sandbox|production`), `credentials` (recommended cast: `encrypted:array`), `status` (`active|inactive|invalid`), `origin_country?`, `external_account_id?`, `last_validated_at?`. Optional `merchant(): BelongsTo` enables the relationship picker **and** automatic scoping; without it, a plain numeric id field is used and no scoping is applied. Merchant resolution order: explicit `::merchantIdUsing()` resolver → Filament tenant (when `tenancy_aware`) → none.

**Shipment model** — expected attributes: `order_id?`, `shipping_connection_id?`, `carrier_code`, `tracking_number?`, `status` (an `Adaa\Shipping\Enums\ShipmentStatus` value), `rate_amount?`, `rate_currency?`, `finalized_at?`, `meta?` (array cast). Optional `order()` / `shippingConnection()` (or `connection()`) relations enrich the columns.

## Panel registration

```php
use Adaa\ShippingFilament\ShippingFilamentPlugin;

$panel->plugin(ShippingFilamentPlugin::make());
```

## Security

- Credentials are stored encrypted by the **host** model and are never rendered in tables or infolists.
- Every list is merchant-scoped when the relationship exists.
- `meta` output passes through `MetaRedactor` before display.

## Testing

```bash
composer test
```

## Releases & automatic updates

Same flow as the core package: maintainer pushes a tag (`git tag v0.1.0 && git push --tags`) → GitHub Action creates the Release → Packagist auto-syncs (one-time setup: submit at <https://packagist.org/packages/submit> with `https://github.com/AdaaSystem/shipping-filament`, then authorize the Packagist GitHub App at <https://packagist.org/profile>). Consumers update with `composer update adaa/shipping-filament`. CI (PHP 8.3–8.5) and weekly Dependabot PRs are already wired.

## Dashboard

Registering the plugin also adds a **Shipping dashboard** page (stats overview, shipments-by-status and by-carrier charts, latest shipments) plus a shipments relation on each connection and an in-transit/active-connections navigation badge. Everything hides itself until both host models are configured.

**Version pairing:** this package follows the core `adaa/shipping` line — it requires `adaa/shipping ^0.1` and is tagged independently (`v0.2.0` covers the dashboard).

## License

MIT
