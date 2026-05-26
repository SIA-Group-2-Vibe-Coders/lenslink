<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure CORS for the API. Set ALLOWED_ORIGINS in your .env file.
    | Use a comma-separated list for multiple origins.
    |
    | Development example:  ALLOWED_ORIGINS=http://localhost:5500,http://127.0.0.1:5500
    | Production example:   ALLOWED_ORIGINS=https://lenslink.vercel.app
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_merge(
        array_map(
            'trim',
            explode(',', env('ALLOWED_ORIGINS', 'http://localhost:5500,http://127.0.0.1:5500,http://localhost:3000'))
        ),
        ['https://lenslink-seven.vercel.app']
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,

];
