<?php

declare(strict_types=1);

namespace Adaa\ShippingFilament\Support;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Factory as HttpFactory;

/**
 * `validateConnection` header action for connection View + Edit pages.
 *
 * Runs ConnectionValidator against the record's decrypted credentials and
 * persists the verdict so `status`/`last_validated_at` stop being
 * write-only: success stores `active` + now, failure stores `invalid`
 * (leaving a previous validation timestamp untouched). Secret values never
 * appear in notifications — only key names and carrier error text.
 */
final class ValidateConnection
{
    public static function make(): Action
    {
        return Action::make('validateConnection')
            ->label('Validate connection')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->action(function (Model $record): void {
                $result = self::run($record);

                if ($result['valid']) {
                    Notification::make()
                        ->success()
                        ->title('Connection valid')
                        ->body($result['live_checked']
                            ? 'Bosta key verified with a live cities lookup.'
                            : 'Required keys present. Live check is not available for this carrier — presence only.')
                        ->send();

                    return;
                }

                Notification::make()
                    ->danger()
                    ->title('Connection invalid')
                    ->body($result['error'] ?? 'Validation failed.')
                    ->send();
            });
    }

    /**
     * Validate a connection record and persist the verdict.
     *
     * @return array{valid: bool, missing: list<string>, live_checked: bool, error: ?string}
     */
    public static function run(Model $record, ?HttpFactory $http = null): array
    {
        $carrierCode = (string) ($record->getAttribute('carrier_code') ?? '');

        $credentials = $record->getAttribute('credentials');

        if (! is_array($credentials)) {
            $credentials = [];
        }

        $environment = (string) ($record->getAttribute('environment') ?? 'sandbox');

        $result = ConnectionValidator::validate($carrierCode, $credentials, $environment, $http);

        if ($result['valid']) {
            $record->forceFill(['status' => 'active', 'last_validated_at' => now()])->save();
        } else {
            $record->forceFill(['status' => 'invalid'])->save();
        }

        return $result;
    }
}
