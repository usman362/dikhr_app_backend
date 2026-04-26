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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // FCM legacy HTTP API config was removed — the placeholder service
    // it pointed to was deprecated by Google in 2024. When push
    // notifications are reimplemented (Firebase Admin SDK, FCM v1 API),
    // add the JSON credentials path here under a 'firebase' key.

    'revenuecat' => [
        'api_key'        => env('REVENUECAT_API_KEY'),
        'webhook_secret' => env('REVENUECAT_WEBHOOK_SECRET'),
    ],

];
