<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*
     | Paths that should have CORS headers applied
     */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
     | Allowed HTTP methods
     */
    'allowed_methods' => ['*'],

    /*
     | Allowed origins (React frontend development servers)
     | Production origins should be set via CORS_ALLOWED_ORIGINS env variable
     */
    'allowed_origins' => explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:3000,http://127.0.0.1:3000,http://localhost:5173,http://127.0.0.1:5173'
    )),

    /*
     | Patterns for dynamic origin matching (e.g., *.example.com)
     */
    'allowed_origins_patterns' => [],

    /*
     | Allowed headers (Accept, Authorization, Content-Type, etc.)
     */
    'allowed_headers' => ['*'],

    /*
     | Headers exposed to the browser
     */
    'exposed_headers' => ['Authorization'],

    /*
     | Max age for preflight request caching (in seconds)
     */
    'max_age' => 3600,

    /*
     | Allow credentials (cookies, authorization headers) to be sent
     | Required for Sanctum authentication
     */
    'supports_credentials' => true,

];
