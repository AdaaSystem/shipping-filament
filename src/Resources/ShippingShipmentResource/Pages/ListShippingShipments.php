<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Resources\ShippingShipmentResource\Pages;

use Adaa\ShippingFilament\Resources\ShippingShipmentResource;
use Filament\Resources\Pages\ListRecords;

class ListShippingShipments extends ListRecords
{
    protected static string $resource = ShippingShipmentResource::class;

    /**
     * Shipments are created by the shipping pipeline, never by admins.
     *
     * @return array<mixed>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
