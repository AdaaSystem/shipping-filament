<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Tests\Support\Fixtures;

use Illuminate\Database\Eloquent\Model;

class TestMerchant extends Model
{
    protected $table = 'test_merchants';

    protected $guarded = [];
}
