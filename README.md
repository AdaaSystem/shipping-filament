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

## الأمان | Security

- بيانات الاعتماد تُخزن مشفرة في `shipping_connections` ولا تُعرض في الجداول أو الـ Infolists.
- كل استعلام مفلتر بملكية التاجر (`merchant_id`) — تاجر لا يرى اتصالات غيره.

## الاختبارات | Testing

```bash
composer test
```

## الترخيص | License

MIT
