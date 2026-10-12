<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament;

use Illuminate\Support\ServiceProvider;

class ShippingFilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/shipping-filament.php', 'shipping-filament');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/shipping-filament.php' => config_path('shipping-filament.php'),
            ], 'shipping-filament-config');
        }
    }
}
