<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Resources;

use Adaa\Shipping\Enums\ConnectionMode;
use Adaa\Shipping\Enums\Environment;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages\CreateShippingConnection;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages\EditShippingConnection;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages\ListShippingConnections;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages\ViewShippingConnection;
use Adaa\ShippingFilament\Resources\ShippingConnectionResource\RelationManagers\ShipmentsRelationManager;
use Adaa\ShippingFilament\Support\CarrierOptions;
use Adaa\ShippingFilament\Support\CredentialFields;
use Adaa\ShippingFilament\Support\ShippingDashboardData;
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
use Filament\Schemas\Components\Utilities\Get;
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
                ->label(__('shipping-filament::connection.merchant'))
                ->relationship('merchant', 'id')
                ->searchable()
                ->preload();
        }

        return TextInput::make('merchant_id')
            ->label(__('shipping-filament::connection.merchant_id'))
            ->numeric();
    }

    /**
     * One required password input per credential key of each carrier,
     * shown only for the selected carrier. State paths nest under the
     * encrypted credentials array; extras merge on save (Create page).
     *
     * @return list<TextInput>
     */
    public static function credentialFields(): array
    {
        $fields = [];

        foreach (CredentialFields::allRequiredByCarrier() as $carrierCode => $keys) {
            foreach ($keys as $key) {
                $fields[] = TextInput::make("credentials.{$key}")
                    ->label(CredentialFields::labelFor($key))
                    ->password()
                    ->revealable()
                    ->required()
                    ->maxLength(2000)
                    ->visible(fn (Get $get): bool => $get('carrier_code') === $carrierCode)
                    ->visibleOn(static::credentialsVisibleOnOperations());
            }
        }

        return $fields;
    }

    /**
     * @return list<Component>
     */
    public static function formComponents(): array
    {
        return [
            static::merchantFormField(),
            Select::make('carrier_code')
                ->label(__('shipping-filament::connection.carrier'))
                ->options(CarrierOptions::options())
                ->required()
                ->searchable()
                ->live(),
            Select::make('mode')
                ->label(__('shipping-filament::connection.mode'))
                ->options([
                    ConnectionMode::Platform->value => __('shipping-filament::connection.platform'),
                    ConnectionMode::Merchant->value => __('shipping-filament::connection.merchant_account'),
                ])
                ->required()
                ->default(ConnectionMode::Platform->value),
            Select::make('environment')
                ->label(__('shipping-filament::connection.environment'))
                ->options([
                    Environment::Sandbox->value => __('shipping-filament::connection.sandbox'),
                    Environment::Production->value => __('shipping-filament::connection.production'),
                ])
                ->required()
                ->default(Environment::Sandbox->value),
            ...static::credentialFields(),
            KeyValue::make('extra_credentials')
                ->label(__('shipping-filament::connection.additional_credentials'))
                ->helperText(__('shipping-filament::connection.additional_credentials_helper'))
                ->keyLabel(__('shipping-filament::connection.key'))
                ->valueLabel(__('shipping-filament::connection.value'))
                ->visibleOn(static::credentialsVisibleOnOperations()),
            Select::make('status')
                ->label(__('shipping-filament::connection.status'))
                ->options([
                    'active' => __('shipping-filament::connection.active'),
                    'inactive' => __('shipping-filament::connection.inactive'),
                    'invalid' => __('shipping-filament::connection.invalid'),
                ])
                ->required()
                ->default('active'),
            TextInput::make('origin_country')
                ->label(__('shipping-filament::connection.origin_country'))
                ->placeholder('EG')
                ->maxLength(2),
            TextInput::make('external_account_id')
                ->label(__('shipping-filament::connection.external_account_id'))
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
            ? TextColumn::make('merchant.id')->label(__('shipping-filament::connection.merchant'))->sortable()
            : TextColumn::make('merchant_id')->label(__('shipping-filament::connection.merchant'))->sortable();

        return [
            TextColumn::make('id')->label('ID')->sortable(),
            $merchantColumn,
            TextColumn::make('carrier_code')->label(__('shipping-filament::connection.carrier'))->searchable()->sortable(),
            TextColumn::make('mode')->label(__('shipping-filament::connection.mode'))->sortable(),
            TextColumn::make('environment')->label(__('shipping-filament::connection.environment'))->sortable(),
            TextColumn::make('status')->label(__('shipping-filament::connection.status'))->sortable(),
            TextColumn::make('last_validated_at')->label(__('shipping-filament::connection.last_validated'))->dateTime()->sortable(),
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
            ? TextEntry::make('merchant.id')->label(__('shipping-filament::connection.merchant'))
            : TextEntry::make('merchant_id')->label(__('shipping-filament::connection.merchant_id'));

        return [
            $merchantEntry,
            TextEntry::make('carrier_code')->label(__('shipping-filament::connection.carrier')),
            TextEntry::make('mode')->label(__('shipping-filament::connection.mode')),
            TextEntry::make('environment')->label(__('shipping-filament::connection.environment')),
            TextEntry::make('status')->label(__('shipping-filament::connection.status'))->badge(),
            TextEntry::make('origin_country')->label(__('shipping-filament::connection.origin_country')),
            TextEntry::make('external_account_id')->label(__('shipping-filament::connection.external_account_id')),
            TextEntry::make('last_validated_at')->label(__('shipping-filament::connection.last_validated'))->dateTime(),
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

    public static function getNavigationLabel(): string
    {
        return __('shipping-filament::connection.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::isConfigured()) {
            return null;
        }

        try {
            $count = ShippingDashboardData::activeConnectionsCount(static::getModel());

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
