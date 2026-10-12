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
            Stat::make('Shipments', number_format($stats['shipments']))
                ->description('Total tracked shipments')
                ->icon(Heroicon::Cube)
                ->color('gray'),
            Stat::make('Delivered', number_format($stats['delivered']))
                ->description($stats['delivered_rate'].'% delivery rate')
                ->icon(Heroicon::CheckBadge)
                ->color('success'),
            Stat::make('In transit', number_format($stats['in_transit']))
                ->description('Currently moving')
                ->icon(Heroicon::Truck)
                ->color('info'),
            Stat::make('Active connections', number_format($stats['active_connections']))
                ->description('Platform + merchant accounts')
                ->icon(Heroicon::Link)
                ->color('warning'),
        ];
    }
}
