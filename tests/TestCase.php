<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Tests;

use Adaa\ShippingFilament\ShippingFilamentServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ShippingFilamentServiceProvider::class];
    }
}
