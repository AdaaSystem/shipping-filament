<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages;

use Adaa\ShippingFilament\Resources\ShippingConnectionResource;
use Adaa\ShippingFilament\Support\ConnectionInspector;
use Adaa\ShippingFilament\Support\ValidateConnection;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewShippingConnection extends ViewRecord
{
    protected static string $resource = ShippingConnectionResource::class;

    /**
     * @return array<EditAction|Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ValidateConnection::make(),
            Action::make('inspectCapabilities')
                ->label('Capabilities')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modalHeading(fn (Model $record): string => 'Capabilities: '.($record->carrier_code ?? 'unknown'))
                ->modalDescription(fn (Model $record): string => implode("\n", ConnectionInspector::describe((string) ($record->carrier_code ?? ''))))
                ->modalSubmitAction(false),
            EditAction::make(),
        ];
    }
}
