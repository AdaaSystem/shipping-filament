<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Resources;

use Adaa\Shipping\Enums\ShipmentStatus;
use Adaa\ShippingFilament\Resources\ShippingShipmentResource\Pages\ListShippingShipments;
use Adaa\ShippingFilament\Resources\ShippingShipmentResource\Pages\ViewShippingShipment;
use Adaa\ShippingFilament\Support\CarrierOptions;
use Adaa\ShippingFilament\Support\MetaRedactor;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Read-only shipment administration. Shipments are created by the shipping
 * pipeline itself, so this resource exposes no create/edit pages.
 */
class ShippingShipmentResource extends BaseShippingResource
{
    protected static ?string $model = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cube;

    protected static ?string $recordTitleAttribute = 'tracking_number';

    public static function getModel(): string
    {
        $model = config('shipping-filament.shipment_model');

        if (is_string($model) && $model !== '') {
            return $model;
        }

        return parent::getModel();
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        $options = [];

        foreach (ShipmentStatus::cases() as $case) {
            $options[$case->value] = (string) str($case->value)->replace('_', ' ')->title();
        }

        return $options;
    }

    public static function statusColor(string $state): string
    {
        return match ($state) {
            ShipmentStatus::Delivered->value => 'success',
            ShipmentStatus::Exception->value, ShipmentStatus::Cancelled->value => 'danger',
            ShipmentStatus::OutForDelivery->value,
            ShipmentStatus::Returned->value,
            ShipmentStatus::Exchanged->value => 'warning',
            ShipmentStatus::PickedUp->value, ShipmentStatus::InTransit->value => 'info',
            default => 'gray',
        };
    }

    protected static function orderColumnName(): string
    {
        if (method_exists(static::getModel(), 'order')) {
            return 'order.id';
        }

        return 'order_id';
    }

    protected static function connectionColumnName(): string
    {
        $model = static::getModel();

        if (method_exists($model, 'shippingConnection')) {
            return 'shippingConnection.id';
        }

        if (method_exists($model, 'connection')) {
            return 'connection.id';
        }

        return 'shipping_connection_id';
    }

    /**
     * @return list<TextColumn>
     */
    public static function tableColumns(): array
    {
        return [
            TextColumn::make('id')->label('ID')->sortable(),
            TextColumn::make(static::orderColumnName())->label('Order')->sortable(),
            TextColumn::make(static::connectionColumnName())->label('Connection')->sortable(),
            TextColumn::make('carrier_code')->label('Carrier')->searchable()->sortable(),
            TextColumn::make('tracking_number')->label('Tracking number')->copyable()->searchable(),
            TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => static::statusColor($state))
                ->formatStateUsing(fn (string $state): string => static::statusOptions()[$state] ?? $state),
            TextColumn::make('rate_amount')
                ->label('Rate')
                ->sortable()
                ->formatStateUsing(fn ($state, Model $record): ?string => static::formatRate($state, $record)),
            TextColumn::make('finalized_at')->label('Finalized')->dateTime()->sortable(),
        ];
    }

    public static function formatRate(mixed $amount, Model $record): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $formatted = number_format((float) $amount, 2);
        $currency = $record->rate_currency ?? null;

        return $currency ? $formatted.' '.$currency : $formatted;
    }

    /**
     * @return list<SelectFilter>
     */
    public static function tableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->options(static::statusOptions()),
            SelectFilter::make('carrier_code')
                ->label('Carrier')
                ->options(CarrierOptions::options()),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::tableColumns())
            ->filters(static::tableFilters())
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    /**
     * Meta is always shown redacted — credential-like keys never leak.
     *
     * @return list<Component>
     */
    public static function infolistComponents(): array
    {
        return [
            TextEntry::make(static::orderColumnName())->label('Order'),
            TextEntry::make(static::connectionColumnName())->label('Connection'),
            TextEntry::make('carrier_code')->label('Carrier'),
            TextEntry::make('tracking_number')->label('Tracking number')->copyable(),
            TextEntry::make('status')->badge(),
            TextEntry::make('rate_amount')->label('Rate')
                ->state(fn (Model $record): ?string => static::formatRate($record->rate_amount ?? null, $record)),
            TextEntry::make('finalized_at')->label('Finalized')->dateTime(),
            KeyValueEntry::make('meta')
                ->keyLabel('Key')
                ->valueLabel('Value')
                ->getStateUsing(fn (Model $record): array => MetaRedactor::redact((array) ($record->meta ?? []))),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components(static::infolistComponents())->columns(2);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListShippingShipments::route('/'),
            'view' => ViewShippingShipment::route('/{record}'),
        ];
    }

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        if ($record === null) {
            return null;
        }

        return (string) ($record->tracking_number ?? $record->getKey());
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::isConfigured()) {
            return null;
        }

        try {
            $count = \Adaa\ShippingFilament\Support\ShippingDashboardData::inTransitCount(static::getModel());

            return $count > 0 ? (string) $count : null;
        } catch (Throwable) {
            return null;
        }
    }
}
