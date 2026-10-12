<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Resources\ShippingConnectionResource\RelationManagers;

use Adaa\ShippingFilament\Resources\ShippingShipmentResource;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipments';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tracking_number')
            ->columns([
                ...array_filter(
                    ShippingShipmentResource::tableColumns(),
                    static fn ($column): bool => in_array(
                        $column->getName(),
                        ['tracking_number', 'carrier_code', 'status', 'rate_amount', 'finalized_at'],
                        true,
                    ),
                ),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
