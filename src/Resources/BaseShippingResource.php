<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Resources;

use Closure;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Throwable;
use UnitEnum;

abstract class BaseShippingResource extends Resource
{
    /**
     * Explicit per-resource merchant resolvers, keyed by resource class.
     *
     * @var array<class-string, Closure|int|string|null>
     */
    protected static array $merchantIdResolvers = [];

    /**
     * Host-model contract: both Eloquent models must be configured before
     * any shipping resource registers navigation or becomes reachable.
     */
    public static function isConfigured(): bool
    {
        return is_string(config('shipping-filament.connection_model'))
            && is_string(config('shipping-filament.shipment_model'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::isConfigured();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $group = config('shipping-filament.navigation_group', 'Shipping');

        return is_string($group) ? $group : null;
    }

    /**
     * Let the host app declare the "current merchant" for query scoping.
     *
     * @param  Closure|int|string|null  $resolver  Static id or a zero-argument closure returning one.
     */
    public static function merchantIdUsing(Closure|int|string|null $resolver): void
    {
        static::$merchantIdResolvers[static::class] = $resolver;
    }

    public static function resolveCurrentMerchantId(): int|string|null
    {
        $resolver = static::$merchantIdResolvers[static::class] ?? null;

        if ($resolver instanceof Closure) {
            $resolved = $resolver();

            return is_int($resolved) || is_string($resolved) ? $resolved : null;
        }

        if (is_int($resolver) || is_string($resolver)) {
            return $resolver;
        }

        if (config('shipping-filament.tenancy_aware', true)) {
            try {
                $tenant = Filament::getTenant();

                if ($tenant !== null) {
                    return $tenant->getKey();
                }
            } catch (Throwable) {
                // No panel/tenant context (e.g. console, tests): no scoping.
            }
        }

        return null;
    }

    /**
     * Scope the query to the current merchant when the host model exposes
     * a merchant() relationship. Models without that relationship are
     * returned unscoped.
     *
     * @param  Builder<*>  $query
     * @return Builder<*>
     */
    public static function applyMerchantScope(Builder $query): Builder
    {
        if (! method_exists(static::getModel(), 'merchant')) {
            return $query;
        }

        $merchantId = static::resolveCurrentMerchantId();

        if ($merchantId === null) {
            return $query;
        }

        return $query->where($query->getModel()->getTable().'.merchant_id', $merchantId);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::applyMerchantScope(parent::getEloquentQuery());
    }
}
