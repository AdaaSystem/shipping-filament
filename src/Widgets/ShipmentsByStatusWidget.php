<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Widgets;

use Adaa\ShippingFilament\Resources\BaseShippingResource;
use Adaa\ShippingFilament\Resources\ShippingShipmentResource;
use Adaa\ShippingFilament\Support\ShippingDashboardData;
use Filament\Widgets\DoughnutChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class ShipmentsByStatusWidget extends DoughnutChartWidget
{
    public static function canView(): bool
    {
        return BaseShippingResource::isConfigured();
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('shipping-filament::dashboard.by_status');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $byStatus = ShippingDashboardData::shipmentsByStatus(
            (string) config('shipping-filament.shipment_model'),
        );

        $options = ShippingShipmentResource::statusOptions();

        return [
            'labels' => array_map(
                static fn (string $status): string => $options[$status] ?? $status,
                array_keys($byStatus),
            ),
            'datasets' => [
                [
                    'data' => array_values($byStatus),
                    'backgroundColor' => [
                        '#9ca3af', '#9ca3af', '#3b82f6', '#3b82f6', '#f59e0b',
                        '#22c55e', '#ef4444', '#ef4444', '#f59e0b', '#a855f7',
                    ],
                ],
            ],
        ];
    }
}
