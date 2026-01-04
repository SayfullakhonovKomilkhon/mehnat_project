<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'health'],

    'allowed_methods' => ['*'],

    // В продакшене установите конкретные домены через CORS_ALLOWED_ORIGINS
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS') 
        ? explode(',', env('CORS_ALLOWED_ORIGINS')) 
        : ['*'],

    'allowed_origins_patterns' => [
        // Разрешить все поддомены Vercel для фронтенда
        '#^https://.*\.vercel\.app$#',
        // Разрешить localhost для разработки
        '#^http://localhost:\d+$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    'max_age' => 86400, // 24 часа кэширования preflight

    'supports_credentials' => true,

];



