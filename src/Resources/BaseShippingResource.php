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
     * Explicit per-model query scopes, keyed by model class. A host app
     * registers these when the default merchant() convention cannot
     * express its ownership (e.g. shipments owned through order->store,
     * or connections where platform rows must stay visible).
     *
     * @var array<class-string, Closure(Builder): Builder|null>
     */
    protected static array $scopeCallbacks = [];

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
     * Register a merchant scope for one host model. The callback
     * receives the query and must return it scoped (or untouched when
     * no merchant resolves). Pass null to remove it (tests).
     */
    public static function scopeUsing(string $modelClass, ?Closure $callback): void
    {
        if ($callback === null) {
            unset(static::$scopeCallbacks[$modelClass]);

            return;
        }

        static::$scopeCallbacks[$modelClass] = $callback;
    }

    /**
     * Scope any host-model query the merchant-aware way: explicit host
     * callback first, then the merchant() convention (merchant rows
     * plus platform rows with a null merchant_id).
     *
     * @param  Builder<*>|null  $query
     * @return Builder<*>
     */
    public static function scopeQueryFor(string $modelClass, ?Builder $query = null): Builder
    {
        $query ??= $modelClass::query();

        $callback = static::$scopeCallbacks[$modelClass] ?? null;

        if ($callback instanceof Closure) {
            return $callback($query);
        }

        if (! method_exists($modelClass, 'merchant')) {
            return $query;
        }

        $merchantId = static::resolveCurrentMerchantId();

        if ($merchantId === null) {
            return $query;
        }

        $instance = new $modelClass;

        return $query->where(function ($where) use ($instance, $merchantId): void {
            $where->where($instance->getTable().'.merchant_id', $merchantId)
                ->orWhereNull($instance->getTable().'.merchant_id');
        });
    }

    /**
     * Scope the query to the current merchant when the host model exposes
     * a merchant() relationship (merchant rows plus null-merchant
     * platform rows). Models without that relationship are returned
     * unscoped unless a scopeUsing() callback was registered.
     *
     * @param  Builder<*>  $query
     * @return Builder<*>
     */
    public static function applyMerchantScope(Builder $query): Builder
    {
        return static::scopeQueryFor(static::getModel(), $query);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::applyMerchantScope(parent::getEloquentQuery());
    }
}
