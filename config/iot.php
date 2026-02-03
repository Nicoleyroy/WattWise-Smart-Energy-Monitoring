<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IoT Device Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the base URL and settings for your IoT device communication.
    | Set IOT_BASE_URL in your .env file.
    |
    */

    'base_url' => env('IOT_BASE_URL', 'http://localhost:8080'),

    'timeout' => env('IOT_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | Define the API endpoints your IoT device uses. Adjust these based on
    | your actual IoT device API structure.
    |
    */

    'endpoints' => [
        'ports' => '/api/ports',
        'port_status' => '/api/ports/{id}',
        'toggle_port' => '/api/ports/{id}/toggle',
        'current_power' => '/api/metrics/power',
        'today_usage' => '/api/metrics/today',
        'alerts' => '/api/alerts',
        'monthly_records' => '/api/records/monthly',
        'thresholds' => '/api/thresholds',
    ],
];

