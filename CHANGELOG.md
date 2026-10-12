# Changelog

## [Unreleased]

- Initial scaffold: Filament plugin contract, publishable config with host-model mapping, Pest skeleton.
- Shipping resources against host-app models: `ShippingConnectionResource` (List/Create/View/Edit; credentials as create-only KeyValue, never in table/infolist/edit) and read-only `ShippingShipmentResource` (List/View; copyable tracking, status badge, status+carrier filters, redacted meta infolist).
- Merchant scoping on connection queries when the host model defines `merchant()` (`merchantIdUsing()` override or Filament tenant fallback); defensive relationship-or-numeric merchant field.
- `CarrierOptions` (hardcoded mirror of `adaa/shipping` `config/carriers.php`) and `MetaRedactor` (pass/secret/token/key redaction) support helpers.
- Pest coverage: hidden-when-unconfigured, plugin registration, encrypted credentials round-trip, credential-free table/infolist, meta redaction, read-only shipment pages, merchant scoping, carrier-list sync.
