<?php

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for IoT Integration
|--------------------------------------------------------------------------
|
| These routes handle communication with IoT devices for the WattWise
| energy monitoring system.
|
*/

Route::middleware(['auth'])->group(function () {
    // Dashboard data endpoints
    Route::get('/dashboard/data', [DashboardController::class, 'getDashboardData'])->name('api.dashboard.data');
    
    // Port/Device control endpoints
    Route::get('/ports', [DashboardController::class, 'getPorts'])->name('api.ports.index');
    Route::post('/ports/{portId}/toggle', [DashboardController::class, 'togglePort'])->name('api.ports.toggle');
    Route::get('/ports/{portId}/status', [DashboardController::class, 'getPortStatus'])->name('api.ports.status');
    
    // Real-time metrics
    Route::get('/metrics/current-power', [DashboardController::class, 'getCurrentPower'])->name('api.metrics.current-power');
    Route::get('/metrics/today-usage', [DashboardController::class, 'getTodayUsage'])->name('api.metrics.today-usage');
    
    // Alerts
    Route::get('/alerts', [DashboardController::class, 'getAlerts'])->name('api.alerts');
    
    // Records/History
    Route::get('/records/monthly', [DashboardController::class, 'getMonthlyRecords'])->name('api.records.monthly');
    
    // Thresholds
    Route::get('/thresholds', [DashboardController::class, 'getThresholds'])->name('api.thresholds');
});

