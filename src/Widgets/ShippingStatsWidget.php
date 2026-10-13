<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Widgets;

use Adaa\ShippingFilament\Resources\BaseShippingResource;
use Adaa\ShippingFilament\Support\ShippingDashboardData;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShippingStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return BaseShippingResource::isConfigured();
    }

    /**
     * @return list<Stat>
     */
    protected function getStats(): array
    {
        $stats = ShippingDashboardData::stats(
            (string) config('shipping-filament.shipment_model'),
            (string) config('shipping-filament.connection_model'),
        );

        return [
            Stat::make(__('shipping-filament::dashboard.shipments'), number_format($stats['shipments']))
                ->description(__('shipping-filament::dashboard.shipments_description'))
                ->icon(Heroicon::Cube)
                ->color('gray'),
            Stat::make(__('shipping-filament::dashboard.delivered'), number_format($stats['delivered']))
                ->description(__('shipping-filament::dashboard.delivered_description', ['rate' => $stats['delivered_rate']]))
                ->icon(Heroicon::CheckBadge)
                ->color('success'),
            Stat::make(__('shipping-filament::dashboard.in_transit'), number_format($stats['in_transit']))
                ->description(__('shipping-filament::dashboard.in_transit_description'))
                ->icon(Heroicon::Truck)
                ->color('info'),
            Stat::make(__('shipping-filament::dashboard.failed'), number_format($stats['failed']))
                ->description(__('shipping-filament::dashboard.failed_description'))
                ->icon(Heroicon::ExclamationTriangle)
                ->color('danger'),
            Stat::make(__('shipping-filament::dashboard.active_connections'), number_format($stats['active_connections']))
                ->description(__('shipping-filament::dashboard.active_connections_description'))
                ->icon(Heroicon::Link)
                ->color('warning'),
        ];
    }
}
