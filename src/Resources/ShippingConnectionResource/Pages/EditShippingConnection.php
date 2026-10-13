<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages;

use Adaa\ShippingFilament\Resources\ShippingConnectionResource;
use Adaa\ShippingFilament\Support\ValidateConnection;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditShippingConnection extends EditRecord
{
    protected static string $resource = ShippingConnectionResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ValidateConnection::make(),
        ];
    }
}
