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
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'shipping-filament');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/shipping-filament.php' => config_path('shipping-filament.php'),
            ], 'shipping-filament-config');

            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/shipping-filament'),
            ], 'shipping-filament-translations');
        }
    }
}
