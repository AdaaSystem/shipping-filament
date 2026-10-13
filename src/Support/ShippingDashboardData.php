<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Support;

use Adaa\Shipping\Enums\ShipmentStatus;
use Adaa\ShippingFilament\Resources\BaseShippingResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Query + aggregation seam behind the dashboard widgets. Kept as plain
 * statics (no Livewire) so the math stays unit-testable against any
 * host models honoring the documented contract.
 */
final class ShippingDashboardData
{
    /**
     * @return array{shipments: int, delivered: int, delivered_rate: float, in_transit: int, failed: int, active_connections: int}
     */
    public static function stats(string $shipmentModel, string $connectionModel): array
    {
        $total = self::scoped($shipmentModel)->count();

        $delivered = self::scoped($shipmentModel)
            ->where('status', ShipmentStatus::Delivered->value)
            ->count();

        $inTransit = self::scoped($shipmentModel)
            ->where('status', ShipmentStatus::InTransit->value)
            ->count();

        $failed = self::scoped($shipmentModel)
            ->whereIn('status', [ShipmentStatus::Exception->value, 'failed'])
            ->count();

        $activeConnections = self::scoped($connectionModel)
            ->where('status', 'active')
            ->count();

        return [
            'shipments' => $total,
            'delivered' => $delivered,
            'delivered_rate' => $total > 0 ? round($delivered / $total * 100, 1) : 0.0,
            'in_transit' => $inTransit,
            'failed' => $failed,
            'active_connections' => $activeConnections,
        ];
    }

    /**
     * Shipment counts keyed by every known status (zero-filled).
     *
     * @return array<string, int>
     */
    public static function shipmentsByStatus(string $shipmentModel): array
    {
        $rows = self::scoped($shipmentModel)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $byStatus = [];

        foreach (ShipmentStatus::cases() as $case) {
            $byStatus[$case->value] = (int) ($rows[$case->value] ?? 0);
        }

        return $byStatus;
    }

    /**
     * Shipment counts keyed by carrier code.
     *
     * @return array<string, int>
     */
    public static function shipmentsByCarrier(string $shipmentModel): array
    {
        /** @var array<string, int> $rows */
        $rows = self::scoped($shipmentModel)
            ->selectRaw('carrier_code, COUNT(*) as aggregate')
            ->groupBy('carrier_code')
            ->pluck('aggregate', 'carrier_code')
            ->all();

        return array_map('intval', $rows);
    }

    /**
     * @return Collection<int, mixed>
     */
    public static function recentShipments(string $shipmentModel, int $limit = 5): Collection
    {
        return self::scoped($shipmentModel)
            ->latest('id')
            ->limit(max(1, $limit))
            ->get();
    }

    public static function activeConnectionsCount(string $connectionModel): int
    {
        return self::scoped($connectionModel)
            ->where('status', 'active')
            ->count();
    }

    public static function inTransitCount(string $shipmentModel): int
    {
        return self::scoped($shipmentModel)
            ->where('status', ShipmentStatus::InTransit->value)
            ->count();
    }

    /**
     * @return Builder<*>
     */
    protected static function scoped(string $model): Builder
    {
        return BaseShippingResource::scopeQueryFor($model);
    }
}
