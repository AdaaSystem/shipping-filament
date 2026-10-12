<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Tests\Support\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestShippingConnection extends Model
{
    protected $table = 'test_shipping_connections';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'last_validated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TestMerchant, $this>
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(TestMerchant::class, 'merchant_id');
    }

    /**
     * @return HasMany<TestShippingShipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(TestShippingShipment::class, 'shipping_connection_id');
    }
}
