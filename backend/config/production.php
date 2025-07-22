<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production-Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings specifically for production environment
    |
    */

    'optimizations' => [
        'config_cache' => true,
        'route_cache' => true,
        'view_cache' => true,
        'event_cache' => true,
    ],

    'security' => [
        'hsts_max_age' => 31536000, // 1 year
        'content_security_policy' => [
            'default-src' => "'self'",
            'script-src' => "'self' 'unsafe-eval' cdnjs.cloudflare.com",
            'style-src' => "'self' 'unsafe-inline' fonts.googleapis.com",
            'font-src' => "'self' fonts.gstatic.com",
            'img-src' => "'self' data: https:",
            'connect-src' => "'self' https:",
        ],
        'force_https' => true,
        'trusted_proxies' => env('TRUSTED_PROXIES', ''),
    ],

    'performance' => [
        'opcache_enabled' => true,
        'query_log_enabled' => false,
        'debug_bar_enabled' => false,
        'telescope_enabled' => false,
    ],

    'monitoring' => [
        'health_checks' => [
            'database' => true,
            'cache' => true,
            'queue' => true,
            'storage' => true,
        ],
        'metrics_collection' => true,
        'error_tracking' => true,
    ],

    'backup' => [
        'enabled' => true,
        'schedule' => 'daily',
        'retention_days' => 30,
        'include' => [
            'database' => true,
            'uploads' => true,
            'logs' => false,
        ],
    ],

    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'url' => env('CDN_URL', ''),
        'assets' => [
            'js' => true,
            'css' => true,
            'images' => true,
        ],
    ],

    'rate_limiting' => [
        'api' => [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
        ],
        'web' => [
            'requests_per_minute' => 100,
        ],
        'auth' => [
            'attempts_per_minute' => 5,
        ],
    ],
];