<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Host application models
    |--------------------------------------------------------------------------
    | The core adaa/shipping package is framework-agnostic and ships no
    | Eloquent models. Point these at YOUR models (which should extend or
    | mirror the connection/shipment concepts documented in adaa/shipping).
    | Resources stay hidden until both models are configured.
    */
    'connection_model' => env('SHIPPING_CONNECTION_MODEL'),
    'shipment_model' => env('SHIPPING_SHIPMENT_MODEL'),

    /*
    |--------------------------------------------------------------------------
    | Panel registration
    |--------------------------------------------------------------------------
    */
    'navigation_group' => env('SHIPPING_NAVIGATION_GROUP', 'Shipping'),
    'tenancy_aware' => env('SHIPPING_TENANCY_AWARE', true),

];
