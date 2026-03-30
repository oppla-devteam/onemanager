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

    'fattureincloud' => [
        'api_key' => env('FATTUREINCLOUD_API_KEY'),
        'api_uid' => env('FATTUREINCLOUD_API_UID'),
        'company_id' => env('FATTUREINCLOUD_COMPANY_ID'),
        'auto_send_sdi' => env('FATTUREINCLOUD_AUTO_SEND_SDI', true),
        'create_immediate_invoices' => env('FATTUREINCLOUD_CREATE_IMMEDIATE', true),
        'create_deferred_invoices' => env('FATTUREINCLOUD_CREATE_DEFERRED', true),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'tookan' => [
        'api_key' => env('TOOKAN_API_KEY'),
    ],

    'bink' => [
        'client_id' => env('BINK_CLIENT_ID'),
        'client_secret' => env('BINK_CLIENT_SECRET'),
    ],

];
