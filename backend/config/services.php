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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    // Microsoft Services
    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'tenant_id' => env('MICROSOFT_TENANT_ID'),
        'redirect_uri' => env('MICROSOFT_REDIRECT_URI'),
        'bookings_url' => env('MICROSOFT_BOOKINGS_URL', 'https://graph.microsoft.com/v1.0/me/calendar/events'),
        'api_key' => env('MICROSOFT_API_KEY'),
    ],

    // Newsletter Service
    'newsletter' => [
        'api_url' => env('NEWSLETTER_API_URL'),
        'api_key' => env('NEWSLETTER_API_KEY'),
    ],

    // Analytics Service
    'analytics' => [
        'api_url' => env('ANALYTICS_API_URL'),
        'api_key' => env('ANALYTICS_API_KEY'),
    ],

    // Monitoring Services
    'sentry' => [
        'dsn' => env('SENTRY_LARAVEL_DSN'),
        'environment' => env('APP_ENV', 'production'),
    ],

    // CDN Configuration
    'cdn' => [
        'url' => env('CDN_URL'),
        'enabled' => env('CDN_ENABLED', false),
    ],

];
