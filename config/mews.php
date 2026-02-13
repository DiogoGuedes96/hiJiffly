<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mews API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Mews Connector API integration
    |
    */

    'api_base_url' => env('MEWS_API_BASE_URL', 'https://api.mews-demo.com'),

    'mews_client' => env('MEWS_CLIENT', 'Hijiffly Integration v1.0.0'),
    
    'client_token' => env('MEWS_CLIENT_TOKEN'),
    
    'access_token' => env('MEWS_ACCESS_TOKEN'),
    
    'timeout' => env('MEWS_API_TIMEOUT', 30),
    
    'retry_times' => env('MEWS_API_RETRY_TIMES', 3),
    
    'timezone_override' => env('MEWS_TIMEZONE_OVERRIDE'),
    
];
