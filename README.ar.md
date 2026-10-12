# Adaa Shipping Filament — واجهة الإدارة الاختيارية

> [English](README.md) | **العربية**

واجهة إدارة Filament اختيارية لـ [`adaa/shipping`](https://github.com/AdaaSystem/shipping). المكتبة الأساسية كاملة بدونها — ثبّت هذه الحزمة فقط إذا كانت لديك لوحة Filament.

## ما تقدمه

- **إدارة اتصالات الشحن** — حسابات المنصة وحسابات التجار، مع تحرير بيانات الاعتماد **عند الإنشاء فقط** وعدم عرضها أبدًا في الجداول أو العروض أو نماذج التحرير.
- **متصفح الشحنات** — عروض تتبع للقراءة فقط مع أرقام تتبع قابلة للنسخ، وشارات حالة، وفلاتر شركات، وعرض آمن للـ metadata (يقنّع `MetaRedactor` أي قيمة `*pass*` أو `*secret*` أو `*token*` أو `*key*` بما فيها المتداخلة).
- **تحديد نطاق التاجر** — تُفلتر القوائم بالتاجر الحالي متى وفّر موديل المضيف علاقة `merchant()`، فلا يرى تاجر اتصالات غيره أبدًا.
- **إخفاء بلا إعداد** — تبقى الموارد غير مسجلة حتى تُضبط موديلات المضيف.

## المتطلبات

- PHP `^8.3`، و`filament/filament ^5.0`، و`adaa/shipping` (نسخة مقابلة).

## التثبيت

```bash
composer require adaa/shipping adaa/shipping-filament
php artisan vendor:publish --tag=shipping-filament-config
```

## الإعداد

وجّه الحزمة إلى موديلات Eloquent **الخاصة بك** (النواة مستقلة عن إطار العمل ولا تشحن أي موديل):

```php
// config/shipping-filament.php — أو عبر .env
'connection_model' => \App\Models\ShippingConnection::class,
'shipment_model' => \App\Models\ShippingShipment::class,
```

```env
SHIPPING_CONNECTION_MODEL=App\Models\ShippingConnection
SHIPPING_SHIPMENT_MODEL=App\Models\ShippingShipment
```

### عقد موديلات المضيف

**موديل الاتصال** — السمات المتوقعة: `merchant_id?`، و`carrier_code`، و`mode` (`platform|merchant`)، و`environment` (`sandbox|production`)، و`credentials` (يُستحسن cast من نوع `encrypted:array`)، و`status` (`active|inactive|invalid`)، و`origin_country?`، و`external_account_id?`، و`last_validated_at?`. علاقة `merchant(): BelongsTo` اختيارية تُفعّل منتقي العلاقات **والتحديد التلقائي للنطاق**؛ بدونها يُستخدم حقل id رقمي عادي دون scoping. ترتيب حل id التاجر: محلل صريح `::merchantIdUsing()` ← مستأجر Filament (عند `tenancy_aware`) ← لا شيء.

**موديل الشحنة** — السمات المتوقعة: `order_id?`، و`shipping_connection_id?`، و`carrier_code`، و`tracking_number?`، و`status` (قيمة من `Adaa\Shipping\Enums\ShipmentStatus`)، و`rate_amount?`، و`rate_currency?`، و`finalized_at?`، و`meta?` (cast من نوع array). علاقات `order()` / `shippingConnection()` (أو `connection()`) اختيارية تُثري الأعمدة.

## التسجيل في اللوحة

```php
use Adaa\ShippingFilament\ShippingFilamentPlugin;

$panel->plugin(ShippingFilamentPlugin::make());
```

## الأمان

- بيانات الاعتماد يخزنها موديل **المضيف** مشفرة ولا تُعرض أبدًا في الجداول أو العروض.
- كل قائمة محددة بنطاق التاجر عند وجود العلاقة.
- مخرجات `meta` تمر عبر `MetaRedactor` قبل العرض.

## الاختبارات

```bash
composer test
```

## الإصدارات والتحديث التلقائي

نفس تدفق الحزمة الأساسية: المشرف يدفع tag (`git tag v0.1.0 && git push --tags`) ← GitHub Action ينشئ Release ← Packagist يتزامن تلقائيًا (إعداد لمرة واحدة: قدّم عبر <https://packagist.org/packages/submit> بالرابط `https://github.com/AdaaSystem/shipping-filament`, ثم خوّل تطبيق Packagist على GitHub في <https://packagist.org/profile>). المشاريع تحدث عبر `composer update adaa/shipping-filament`. الـ CI (PHP 8.3–8.5) وطلبات Dependabot الأسبوعية مربوطة مسبقًا.

**تزامن النسخ:** تتبع هذه الحزمة نسخة `adaa/shipping` الأساسية — وسِم النسخ المتطابقة معًا (مثل الأساسية `v0.1.0` مع filament برقم `v0.1.0`).

## الترخيص

MIT
