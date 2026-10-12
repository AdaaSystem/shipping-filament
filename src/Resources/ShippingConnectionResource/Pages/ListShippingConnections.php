<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages;

use Adaa\ShippingFilament\Resources\ShippingConnectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShippingConnections extends ListRecords
{
    protected static string $resource = ShippingConnectionResource::class;

    /**
     * @return array<CreateAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
