<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages;

use Adaa\ShippingFilament\Resources\ShippingConnectionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewShippingConnection extends ViewRecord
{
    protected static string $resource = ShippingConnectionResource::class;

    /**
     * @return array<EditAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
