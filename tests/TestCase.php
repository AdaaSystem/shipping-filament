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

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('t', 32)));
    }
}
