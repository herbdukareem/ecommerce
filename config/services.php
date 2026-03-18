<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY', env('PAYSTACK_SK')),
        'public_key' => env('PAYSTACK_PUBLIC_KEY', env('PAYSTACK_PK')),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET', env('PAYSTACK_SECRET_KEY', env('PAYSTACK_SK'))),
    ],

    'flutterwave' => [
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
        'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com/v3'),
        'redirect_url' => env('FLUTTERWAVE_REDIRECT_URL'),
        'webhook_secret' => env('FLUTTERWAVE_WEBHOOK_SECRET'),
        'currency' => env('FLUTTERWAVE_CURRENCY', 'NGN'),
    ],

    'dhl' => [
        'api_key' => env('DHL_API_KEY'),
        'api_secret' => env('DHL_API_SECRET'),
    ],

    'fedex' => [
        'api_key' => env('FEDEX_API_KEY'),
        'api_secret' => env('FEDEX_API_SECRET'),
    ],

    'geo' => [
        'autocomplete_provider' => env('GEO_AUTOCOMPLETE_PROVIDER', 'none'),
        'geoapify_key' => env('GEOAPIFY_KEY'),
        'google_places_key' => env('GOOGLE_PLACES_KEY'),
    ],

];
