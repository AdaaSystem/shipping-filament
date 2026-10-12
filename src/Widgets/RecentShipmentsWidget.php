<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Widgets;

use Adaa\ShippingFilament\Resources\BaseShippingResource;
use Adaa\ShippingFilament\Resources\ShippingShipmentResource;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentShipmentsWidget extends TableWidget
{
    public static function canView(): bool
    {
        return BaseShippingResource::isConfigured();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ShippingShipmentResource::getEloquentQuery()->latest('id')->limit(5))
            ->columns([
                ...array_filter(
                    ShippingShipmentResource::tableColumns(),
                    static fn ($column): bool => in_array(
                        $column->getName(),
                        ['tracking_number', 'carrier_code', 'status', 'finalized_at'],
                        true,
                    ),
                ),
            ])
            ->paginated(false);
    }
}
