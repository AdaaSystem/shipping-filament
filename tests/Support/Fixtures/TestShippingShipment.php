<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Tests\Support\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestShippingShipment extends Model
{
    protected $table = 'test_shipping_shipments';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'finalized_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TestShippingConnection, $this>
     */
    public function shippingConnection(): BelongsTo
    {
        return $this->belongsTo(TestShippingConnection::class, 'shipping_connection_id');
    }
}
