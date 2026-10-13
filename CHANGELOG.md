# Changelog

## [Unreleased]

## [v0.3.0]

- Per-carrier dynamic credential fields on the connection form (driven by core `required_credentials`) + additional-credentials merge on save.
- English/Arabic interface translations (`lang/en`, `lang/ar`, publishable).

- `validateConnection` header action on connection View + Edit pages: offline required-keys presence check for every carrier plus a live Bosta `cities()` probe; persists `active` + `last_validated_at` on success and `invalid` on failure, never logging or displaying secret values.

## [v0.2.1]

- Merchant-aware scoping hook (`scopeUsing`): platform connections stay visible to merchants by default; hosts can override per model.
- Failed stat on the dashboard (exception + failed rows).

## [v0.2.0]

- Shipping dashboard page (stats, status/carrier charts, latest shipments).
- Shipments relation manager on connections; navigation badges; credential-free capability inspector action.
- `ShippingDashboardData` aggregation seam (unit-tested, merchant-aware).

- Initial scaffold: Filament plugin contract, publishable config with host-model mapping, Pest skeleton.
- Shipping resources against host-app models: `ShippingConnectionResource` (List/Create/View/Edit; credentials as create-only KeyValue, never in table/infolist/edit) and read-only `ShippingShipmentResource` (List/View; copyable tracking, status badge, status+carrier filters, redacted meta infolist).
- Merchant scoping on connection queries when the host model defines `merchant()` (`merchantIdUsing()` override or Filament tenant fallback); defensive relationship-or-numeric merchant field.
- `CarrierOptions` (hardcoded mirror of `adaa/shipping` `config/carriers.php`) and `MetaRedactor` (pass/secret/token/key redaction) support helpers.
- Pest coverage: hidden-when-unconfigured, plugin registration, encrypted credentials round-trip, credential-free table/infolist, meta redaction, read-only shipment pages, merchant scoping, carrier-list sync.
