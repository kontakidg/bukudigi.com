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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
    ],

    'fonnte' => [
        'mode' => env('FONNTE_MODE', 'stub'),
        'token' => env('FONNTE_TOKEN'),
        'url' => env('FONNTE_URL', 'https://api.fonnte.com/send'),
    ],

    'facebook' => [
        // App ID untuk OG meta tag fb:app_id (recommended untuk Facebook Insights tracking).
        // Kosongkan kalau belum punya Facebook App.
        'app_id' => env('FACEBOOK_APP_ID'),
    ],

    'midtrans' => [
        // 'stub' = simulasi tanpa Midtrans (dev). 'live' = beneran call API.
        'mode' => env('MIDTRANS_MODE', 'stub'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        // URL callback — kalau kosong fallback ke APP_URL + route default
        'notify_url' => env('MIDTRANS_NOTIFY_URL'),
        'finish_url' => env('MIDTRANS_FINISH_URL'),
        'unfinish_url' => env('MIDTRANS_UNFINISH_URL'),
        'error_url' => env('MIDTRANS_ERROR_URL'),
        // Boleh enable 3DS untuk credit card
        'enable_3ds' => env('MIDTRANS_ENABLE_3DS', true),
    ],

    'ghostscript' => [
        // Path absolut ke gswin64c.exe (Windows) atau gs (Linux). Kosong = auto-detect.
        'binary' => env('GHOSTSCRIPT_BIN'),
    ],

];
