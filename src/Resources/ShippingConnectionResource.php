<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Resources;

use Adaa\Shipping\Enums\ConnectionMode;
use Adaa\Shipping\Enums\Environment;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource\RelationManagers\ShipmentsRelationManager;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages\CreateShippingConnection;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages\EditShippingConnection;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages\ListShippingConnections;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages\ViewShippingConnection;
use Adaa\ShippingFilament\Support\CarrierOptions;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class ShippingConnectionResource extends BaseShippingResource
{
    protected static ?string $model = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Truck;

    protected static ?string $recordTitleAttribute = 'carrier_code';

    public static function getModel(): string
    {
        $model = config('shipping-filament.connection_model');

        if (is_string($model) && $model !== '') {
            return $model;
        }

        return parent::getModel();
    }

    /**
     * Credentials may only ever be entered on the create page. The runtime
     * enforcement is Filament's visibleOn(); this helper keeps the policy
     * in one declarative place (and assertable place).
     *
     * @return list<string>
     */
    public static function credentialsVisibleOnOperations(): array
    {
        return ['create'];
    }

    /**
     * Host-model contract: when the connection model defines merchant(), the
     * merchant is picked through that relationship; otherwise a raw numeric
     * merchant_id is accepted.
     */
    public static function merchantFormField(): Component
    {
        if (method_exists(static::getModel(), 'merchant')) {
            return Select::make('merchant_id')
                ->label('Merchant')
                ->relationship('merchant', 'id')
                ->searchable()
                ->preload();
        }

        return TextInput::make('merchant_id')
            ->label('Merchant ID')
            ->numeric();
    }

    /**
     * @return list<Component>
     */
    public static function formComponents(): array
    {
        return [
            static::merchantFormField(),
            Select::make('carrier_code')
                ->label('Carrier')
                ->options(CarrierOptions::options())
                ->required()
                ->searchable(),
            Select::make('mode')
                ->options([
                    ConnectionMode::Platform->value => 'Platform',
                    ConnectionMode::Merchant->value => 'Merchant',
                ])
                ->required()
                ->default(ConnectionMode::Platform->value),
            Select::make('environment')
                ->options([
                    Environment::Sandbox->value => 'Sandbox',
                    Environment::Production->value => 'Production',
                ])
                ->required()
                ->default(Environment::Sandbox->value),
            KeyValue::make('credentials')
                ->label('Credentials')
                ->helperText('Stored encrypted. Can only be set here — never shown again.')
                ->keyLabel('Key')
                ->valueLabel('Value')
                ->keyPlaceholder('e.g. api_key')
                ->valuePlaceholder('Secret value')
                ->addActionLabel('Add credential')
                ->visibleOn(static::credentialsVisibleOnOperations()),
            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'invalid' => 'Invalid',
                ])
                ->required()
                ->default('active'),
            TextInput::make('origin_country')
                ->label('Origin country')
                ->placeholder('EG')
                ->maxLength(2),
            TextInput::make('external_account_id')
                ->label('External account ID')
                ->maxLength(255),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::formComponents())->columns(2);
    }

    /**
     * Credential columns must never appear here. This list is the single
     * source for table() and is asserted credential-free in tests.
     *
     * @return list<TextColumn>
     */
    public static function tableColumns(): array
    {
        $merchantColumn = method_exists(static::getModel(), 'merchant')
            ? TextColumn::make('merchant.id')->label('Merchant')->sortable()
            : TextColumn::make('merchant_id')->label('Merchant')->sortable();

        return [
            TextColumn::make('id')->label('ID')->sortable(),
            $merchantColumn,
            TextColumn::make('carrier_code')->label('Carrier')->searchable()->sortable(),
            TextColumn::make('mode')->sortable(),
            TextColumn::make('environment')->sortable(),
            TextColumn::make('status')->sortable(),
            TextColumn::make('last_validated_at')->label('Last validated')->dateTime()->sortable(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::tableColumns())
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return list<Component>
     */
    public static function infolistComponents(): array
    {
        $merchantEntry = method_exists(static::getModel(), 'merchant')
            ? TextEntry::make('merchant.id')->label('Merchant')
            : TextEntry::make('merchant_id')->label('Merchant ID');

        return [
            $merchantEntry,
            TextEntry::make('carrier_code')->label('Carrier'),
            TextEntry::make('mode'),
            TextEntry::make('environment'),
            TextEntry::make('status')->badge(),
            TextEntry::make('origin_country')->label('Origin country'),
            TextEntry::make('external_account_id')->label('External account ID'),
            TextEntry::make('last_validated_at')->label('Last validated')->dateTime(),
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
            'index' => ListShippingConnections::route('/'),
            'create' => CreateShippingConnection::route('/create'),
            'view' => ViewShippingConnection::route('/{record}'),
            'edit' => EditShippingConnection::route('/{record}/edit'),
        ];
    }

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        if ($record === null) {
            return null;
        }

        return (string) ($record->carrier_code ?? $record->getKey());
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::isConfigured()) {
            return null;
        }

        try {
            $count = \Adaa\ShippingFilament\Support\ShippingDashboardData::activeConnectionsCount(static::getModel());

            return $count > 0 ? (string) $count : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function getRelations(): array
    {
        return [
            ShipmentsRelationManager::class,
        ];
    }
}
