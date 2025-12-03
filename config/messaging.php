<?php
return [
    'default' => env('MESSAGING_BROKER', 'redis'),
    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
    ]
];
