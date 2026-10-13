<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Pages;

use Adaa\ShippingFilament\Resources\BaseShippingResource;
use Adaa\ShippingFilament\Widgets\RecentShipmentsWidget;
use Adaa\ShippingFilament\Widgets\ShipmentsByCarrierWidget;
use Adaa\ShippingFilament\Widgets\ShipmentsByStatusWidget;
use Adaa\ShippingFilament\Widgets\ShippingStatsWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ShippingDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?string $title = null;

    public static function getNavigationLabel(): string
    {
        return __('shipping-filament::nav.dashboard');
    }

    public function getTitle(): string|Htmlable
    {
        return __('shipping-filament::dashboard.title');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return BaseShippingResource::isConfigured();
    }

    public static function canAccess(): bool
    {
        return BaseShippingResource::isConfigured();
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return BaseShippingResource::getNavigationGroup();
    }

    /**
     * @return list<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            ShippingStatsWidget::class,
        ];
    }

    /**
     * @return list<class-string>
     */
    protected function getFooterWidgets(): array
    {
        return [
            ShipmentsByStatusWidget::class,
            ShipmentsByCarrierWidget::class,
            RecentShipmentsWidget::class,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(1)
                ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getHeaderWidgets())),
            Grid::make(2)
                ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getFooterWidgets())),
        ]);
    }
}
