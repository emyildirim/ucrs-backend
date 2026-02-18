<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for cross-origin resource sharing. Determines what
    | cross-origin operations may execute in web browsers.
    |
    | Learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        '*'// http://localhost:3000,http://127.0.0.1:3000,http://localhost:5173,http://127.0.0.1:5173
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 3600,

    'supports_credentials' => true,

];
