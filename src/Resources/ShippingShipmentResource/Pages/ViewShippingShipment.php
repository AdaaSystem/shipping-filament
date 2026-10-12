<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Resources\ShippingShipmentResource\Pages;

use Adaa\ShippingFilament\Resources\ShippingShipmentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewShippingShipment extends ViewRecord
{
    protected static string $resource = ShippingShipmentResource::class;

    /**
     * @return array<mixed>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
