# Adaa Shipping Filament — واجهة الإدارة الاختيارية

> Optional Filament admin UI for [`adaa/shipping`](https://github.com/AdaaSystem/shipping).
> حزمة اختيارية تمامًا — المكتبة الأساسية كاملة بدونها.

## التثبيت | Installation

```bash
composer require adaa/shipping adaa/shipping-filament
php artisan vendor:publish --tag=shipping-filament-config
```

## الإعداد | Configuration

```php
// config/shipping-filament.php — أو عبر .env
'connection_model' => \App\Models\ShippingConnection::class,
'shipment_model' => \App\Models\ShippingShipment::class,
```

```env
SHIPPING_CONNECTION_MODEL=App\Models\ShippingConnection
SHIPPING_SHIPMENT_MODEL=App\Models\ShippingShipment
```

## التسجيل في اللوحة | Panel registration

```php
use Adaa\ShippingFilament\ShippingFilamentPlugin;

$panel->plugin(ShippingFilamentPlugin::make());
```

تبقى الموارد مخفية حتى تُضبط الموديلات — لا أسرار خام تُعرض أبدًا (حقول كلمات مرور + إظهار عند الإنشاء فقط).

## الموارد | Resources

- `ShippingConnectionResource` (+ List/Create/View/Edit): إدارة اتصالات الشحن.
  الحقول: التاجر (`merchant()` relationship إن وُجدت وإلا `merchant_id` رقمي)،
  `carrier_code` (قائمة ثابتة تطابق `config/carriers.php` في `adaa/shipping`)،
  `mode` (platform/merchant)، `environment` (sandbox/production)،
  `credentials` (KeyValue **عند الإنشاء فقط** — لا تظهر في الجدول أو العرض أو التعديل)،
  `status`، `origin_country`، `external_account_id`.
  الجدول: id، التاجر، الناقل، الوضع، البيئة، الحالة، `last_validated_at` — **لا أعمدة أسرار أبدًا**.
- `ShippingShipmentResource` (+ List/View فقط — للقراءة): الشحنات تُنشأ من خط الشحن نفسه.
  الجدول: الطلب، الاتصال/الناقل، `tracking_number` (قابل للنسخ)، الحالة (شارة ملونة)،
  السعر، `finalized_at`؛ فلاتر الحالة + الناقل؛ صفحة العرض تُظهر `meta` بعد التنقية
  (أي مفتاح يحتوي pass/secret/token/key يُستبدل بـ `••••••`).

## عقد موديلات المضيف | Host-model contract

الحزمة الأساسية لا تشحن موديلات Eloquent — عرّف موديلاتك الخاصة ثم اربطها:

```php
// config/shipping-filament.php
'connection_model' => \App\Models\ShippingConnection::class,
'shipment_model' => \App\Models\ShippingShipment::class,
```

### موديل الاتصال | Connection model

الأعمدة المطلوبة: `merchant_id` (nullable)، `carrier_code`، `mode`، `environment`،
`credentials`، `status` (`active`/`inactive`/`invalid`)، `origin_country` (nullable)،
`external_account_id` (nullable)، `last_validated_at` (nullable)، `timestamps`.

- يُستحسن ترميز الأسرار: `'credentials' => 'encrypted:array'` في `casts()`.
- إن عرّف الموديل `merchant(): BelongsTo` استُخدمت قائمة علاقة، وإلا حقل `merchant_id` رقمي.
- فلترة التاجر: عندما توجد علاقة `merchant()` يُفلتر الاستعلام على `merchant_id`
  للمعرف الحالي — حدّده عبر `ShippingConnectionResource::merchantIdUsing(fn () => ...)`،
  أو تلقائيًا من مستأجر Filament الحالي عندما `tenancy_aware=true` (الافتراضي).
  بدون علاقة `merchant()` أو بدون معرف حالي: لا فلترة.

### موديل الشحنة | Shipment model

الأعمدة المطلوبة: `order_id` (nullable)، `shipping_connection_id` (nullable)،
`carrier_code`، `tracking_number` (nullable)، `status` (إحدى قيم `ShipmentStatus`)،
`rate_amount` (nullable)، `rate_currency` (nullable)، `finalized_at` (nullable)،
`meta` (nullable, مصبوب `array`)، `timestamps`.

- علاقات اختيارية لعرض أجمل: `order(): BelongsTo`، `shippingConnection(): BelongsTo`
  (أو `connection(): BelongsTo`).
- `meta` تُعرض دائمًا بعد تنقية `MetaRedactor` — لا تعرضها خامًا في أي مكان.

## الأمان | Security

- بيانات الاعتماد تُخزن مشفرة في `shipping_connections` ولا تُعرض في الجداول أو الـ Infolists.
- كل استعلام مفلتر بملكية التاجر (`merchant_id`) — تاجر لا يرى اتصالات غيره.

## الاختبارات | Testing

```bash
composer test
```

## الترخيص | License

MIT
