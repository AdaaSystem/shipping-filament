<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Tests\Support\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Connection fixture with no merchant() relationship, exercising the
 * numeric merchant_id fallback in the resource form.
 */
class TestShippingConnectionWithoutMerchant extends Model
{
    protected $table = 'test_shipping_connections_plain';

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
}
