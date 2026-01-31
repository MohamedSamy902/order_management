<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway used by the application.
    | You may change this as needed but the value must be one of the available
    | gateways configured below.
    |
    */

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'myfatoorah'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the payment gateways for your application.
    | Each gateway has its own configuration settings for test and live modes.
    |
    */

    'gateways' => [
        'myfatoorah' => [
            'test_mode' => env('MYFATOORAH_TEST_MODE', true),
            'test_api_key' => env('MYFATOORAH_TEST_API_KEY'),
            'live_api_key' => env('MYFATOORAH_LIVE_API_KEY'),
            'test_url' => env('MYFATOORAH_TEST_URL', 'https://apitest.myfatoorah.com'),
            'live_url' => env('MYFATOORAH_LIVE_URL', 'https://api.myfatoorah.com'),
            'currency' => env('MYFATOORAH_CURRENCY', 'KWD'),
            'callback_url' => env('MYFATOORAH_CALLBACK_URL', env('APP_URL') . '/api/v1/payments/callback/myfatoorah'),
            'error_url' => env('MYFATOORAH_ERROR_URL', env('APP_URL') . '/api/v1/payments/callback/myfatoorah'),
        ],

        'tabby' => [
            'test_mode' => env('TABBY_TEST_MODE', true),
            'public_key' => env('TABBY_PUBLIC_KEY'),
            'secret_key' => env('TABBY_SECRET_KEY'),
            'merchant_code' => env('TABBY_MERCHANT_CODE'),
            'currency' => env('TABBY_CURRENCY', 'SAR'),
            'callback_url' => env('TABBY_CALLBACK_URL', env('APP_URL') . '/api/v1/payments/callback/tabby'),
        ],

        'tamara' => [
            'test_mode' => env('TAMARA_TEST_MODE', true),
            'test_api_token' => env('TAMARA_TEST_API_TOKEN'),
            'live_api_token' => env('TAMARA_LIVE_API_TOKEN'),
            'test_public_key' => env('TAMARA_TEST_PUBLIC_KEY'),
            'live_public_key' => env('TAMARA_LIVE_PUBLIC_KEY'),
            'test_notification_token' => env('TAMARA_TEST_NOTIFICATION_TOKEN'),
            'live_notification_token' => env('TAMARA_LIVE_NOTIFICATION_TOKEN'),
            'test_url' => env('TAMARA_TEST_URL', 'https://api-sandbox.tamara.co'),
            'live_url' => env('TAMARA_LIVE_URL', 'https://api.tamara.co'),
            'currency' => env('TAMARA_CURRENCY', 'SAR'),
            'callback_url' => env('TAMARA_CALLBACK_URL', env('APP_URL') . '/api/v1/payments/callback/tamara'),
            'webhook_url' => env('TAMARA_WEBHOOK_URL', env('APP_URL') . '/api/v1/payments/webhook/tamara'),
        ],
    ],
];
