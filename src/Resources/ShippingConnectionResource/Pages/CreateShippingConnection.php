<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages;

use Adaa\ShippingFilament\Resources\ShippingConnectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShippingConnection extends CreateRecord
{
    protected static string $resource = ShippingConnectionResource::class;
}
