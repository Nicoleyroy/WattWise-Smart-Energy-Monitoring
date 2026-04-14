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
    | Firebase Real-time Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Firebase Realtime Database for ESP32 communication.
    | Your ESP32 devices write to and read from Firebase in real-time.
    |
    */

    'firebase' => [
        'database_url' => env('FIREBASE_DATABASE_URL', 'https://wattwise-1d764-default-rtdb.firebaseio.com'),
        'api_key' => env('FIREBASE_API_KEY', null),
        'verify_ssl' => env('FIREBASE_VERIFY_SSL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Electricity Rate Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the electricity rate for cost calculations.
    | Default rate is in PHP per kWh.
    |
    */

    'electricity_rate' => env('ELECTRICITY_RATE', 12.5),

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

