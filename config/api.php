<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | This value controls the number of requests that can be made to the API
    | within a given time frame. You can adjust this value to suit your needs.
    |
    */

    'rate_limit' => env('API_RATE_LIMIT', 100),

];  