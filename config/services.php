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

    'orange_sms' => [
        'base_url' => env('ORANGE_BASE_URL', 'https://api.orange.com'),
        'app_id' => env('ORANGE_APP_ID'),
        'client_id' => env('ORANGE_CLIENT_ID'),
        'client_secret' => env('ORANGE_CLIENT_SECRET'),
        'auth_header' => env('ORANGE_AUTH_HEADER'),
        'sender_phone' => env('ORANGE_SENDER_PHONE'),
        'sender_name' => env('ORANGE_SENDER_NAME', 'CCAK'),
        'default_country_code' => env('ORANGE_DEFAULT_COUNTRY_CODE', '+221'),
    ],

    'ccak' => [
        'base_url' => env('CCAK_API_URL'),
        'api_key'  => env('CCAK_API_KEY'),
    ],

];
