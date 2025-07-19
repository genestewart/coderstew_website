<?php

return [
    'route_prefix' => env('BACKPACK_ROUTE_PREFIX', 'admin'),
    'middleware' => [
        'web',
        'auth',
    ],
];
