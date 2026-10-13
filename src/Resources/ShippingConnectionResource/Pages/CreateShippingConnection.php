<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Resources\ShippingConnectionResource\Pages;

use Adaa\ShippingFilament\Resources\ShippingConnectionResource;
use Adaa\ShippingFilament\Support\CredentialFields;
use Filament\Resources\Pages\CreateRecord;

class CreateShippingConnection extends CreateRecord
{
    protected static string $resource = ShippingConnectionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return CredentialFields::mergeExtra($data);
    }
}
