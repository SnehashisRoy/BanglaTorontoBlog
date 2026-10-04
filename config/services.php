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

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
    ],

    'google_analytics' => [
        'id' => env('GOOGLE_ANALYTICS_ID'),
    ],

    'cj_dropshipping' => [
        'base_url' => env('CJ_DROPSHIPPING_BASE_URL', 'https://developers.cjdropshipping.com/api2.0/v1'),
        // CJ always quotes prices in USD (no currency param on their API).
        // Used only if the live rate lookup fails and nothing is cached yet.
        'usd_to_cad_fallback_rate' => (float) env('CJ_USD_TO_CAD_FALLBACK_RATE', 1.38),
    ],

];
