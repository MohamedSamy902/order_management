<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default API Version
    |--------------------------------------------------------------------------
    |
    | This is the default API version that will be used when no version
    | is specified in the request.
    |
    */
    'default_version' => env('API_DEFAULT_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | API Versions
    |--------------------------------------------------------------------------
    |
    | Define all available API versions and their status.
    | Status: active, deprecated, beta
    |
    */
    'versions' => [
        'v1' => [
            'status' => 'active',
            'deprecated' => false,
            'sunset_date' => null,
            'description' => 'Original API without variants and OTP support',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Deprecation Headers
    |--------------------------------------------------------------------------
    |
    | Control which deprecation headers should be sent in responses.
    |
    */
    'deprecation_headers' => [
        'sunset' => true,
        'deprecation' => true,
        'link' => true,
    ],
];
