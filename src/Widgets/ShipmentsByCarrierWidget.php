<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Widgets;

use Adaa\ShippingFilament\Resources\BaseShippingResource;
use Adaa\ShippingFilament\Support\CarrierOptions;
use Adaa\ShippingFilament\Support\ShippingDashboardData;
use Filament\Widgets\BarChartWidget;

class ShipmentsByCarrierWidget extends BarChartWidget
{
    protected ?string $heading = 'Shipments by carrier';

    public static function canView(): bool
    {
        return BaseShippingResource::isConfigured();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $byCarrier = ShippingDashboardData::shipmentsByCarrier(
            (string) config('shipping-filament.shipment_model'),
        );

        return [
            'labels' => array_map(
                static fn (string $code): string => CarrierOptions::label($code),
                array_keys($byCarrier),
            ),
            'datasets' => [
                [
                    'label' => 'Shipments',
                    'data' => array_values($byCarrier),
                    'backgroundColor' => '#3b82f6',
                ],
            ],
        ];
    }
}
