<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IoTController;
use App\Http\Controllers\Api\MaintenanceAlertController;
use App\Http\Controllers\Api\MonthlyElectricityBillController;
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

// Public endpoint for ESP32 to send energy data (no auth required)
Route::post('/iot/energy', [IoTController::class, 'storeEnergyReading'])->name('api.iot.energy');

// Protected endpoints (uses 'web' middleware for session/cookie auth from Inertia frontend)
Route::middleware(['web', 'auth'])->group(function () {
    // IoT data retrieval (for authenticated users)
    Route::get('/iot/readings', [IoTController::class, 'getLatestReadings'])->name('api.iot.readings');
    Route::get('/iot/statistics', [IoTController::class, 'getStatistics'])->name('api.iot.statistics');
    Route::get('/iot/history', [IoTController::class, 'getHistory'])->name('api.iot.history');
    Route::delete('/devices/{deviceId}/energy', [IoTController::class, 'clearEnergyData'])->name('api.devices.energy.clear');
    // Dashboard data endpoints
    Route::get('/dashboard/data', [DashboardController::class, 'getDashboardData'])->name('api.dashboard.data');
    
    // Port/Device control endpoints
    Route::get('/ports', [DashboardController::class, 'getPorts'])->name('api.ports.index');
    Route::post('/ports/{portId}/toggle', [DashboardController::class, 'togglePort'])->name('api.ports.toggle');
    Route::get('/ports/{portId}/status', [DashboardController::class, 'getPortStatus'])->name('api.ports.status');
    
    // Real-time metrics
    Route::get('/metrics/current-power', [DashboardController::class, 'getCurrentPower'])->name('api.metrics.current-power');
    Route::get('/metrics/today-usage', [DashboardController::class, 'getTodayUsage'])->name('api.metrics.today-usage');
    
    // Maintenance Alerts
    Route::get('/alerts', [MaintenanceAlertController::class, 'index'])->name('api.alerts');
    Route::post('/alerts', [MaintenanceAlertController::class, 'store'])->name('api.alerts.store');
    Route::delete('/alerts/{id}', [MaintenanceAlertController::class, 'destroy'])->name('api.alerts.destroy');
    Route::post('/alerts/{id}/read', [MaintenanceAlertController::class, 'markAsRead'])->name('api.alerts.read');
    Route::post('/alerts/threshold-exceeded', [MaintenanceAlertController::class, 'triggerThresholdAlert'])->name('api.alerts.threshold-exceeded');
    
    // Records/History
    Route::get('/records/monthly', [DashboardController::class, 'getMonthlyRecords'])->name('api.records.monthly');
    Route::get('/devices/{deviceId}/energy/export', [IoTController::class, 'exportEnergyData'])->name('api.devices.energy.export');
    
    // Thresholds
    Route::get('/thresholds', [DashboardController::class, 'getThresholds'])->name('api.thresholds');
    Route::post('/devices/{deviceId}/threshold', [DashboardController::class, 'setThreshold'])->name('api.devices.threshold.set');
    Route::get('/devices/{deviceId}/threshold', [DashboardController::class, 'getDeviceThreshold'])->name('api.devices.threshold.get');
    Route::post('/devices/{deviceId}/name', [DashboardController::class, 'setDeviceName'])->name('api.devices.name.set');
    Route::get('/devices/{deviceId}/monthly-bill', [MonthlyElectricityBillController::class, 'show']);
    Route::post('/devices/{deviceId}/monthly-bill', [MonthlyElectricityBillController::class, 'store']);

    // Notifications
    Route::post('/notifications/{id}/read', [IoTController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [IoTController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [IoTController::class, 'deleteNotification']);
    
    // Device Schedule (Single)
    Route::get('/devices/{deviceId}/schedule', [\App\Http\Controllers\Api\DeviceScheduleController::class, 'show']);
    Route::get('/devices/{deviceId}/schedules', [\App\Http\Controllers\Api\DeviceScheduleController::class, 'index']);
    Route::post('/devices/{deviceId}/schedules', [\App\Http\Controllers\Api\DeviceScheduleController::class, 'storeItem']);
    Route::put('/devices/{deviceId}/schedules/{scheduleId}', [\App\Http\Controllers\Api\DeviceScheduleController::class, 'updateItem']);
    Route::delete('/devices/{deviceId}/schedules/{scheduleId}', [\App\Http\Controllers\Api\DeviceScheduleController::class, 'destroyItem']);
    Route::post('/devices/{deviceId}/schedules/{scheduleId}/activate', [\App\Http\Controllers\Api\DeviceScheduleController::class, 'activateItem']);
    Route::get('/devices/{deviceId}/schedule/history', [\App\Http\Controllers\Api\DeviceScheduleController::class, 'history']);
    Route::post('/devices/{deviceId}/schedule/history/{historyId}/archive', [\App\Http\Controllers\Api\DeviceScheduleController::class, 'archiveHistory']);
    Route::post('/devices/{deviceId}/schedule', [\App\Http\Controllers\Api\DeviceScheduleController::class, 'update']);
    Route::delete('/devices/{deviceId}/schedule', [\App\Http\Controllers\Api\DeviceScheduleController::class, 'destroy']);
    Route::post('/devices/{deviceId}/schedule/activate', [\App\Http\Controllers\Api\DeviceScheduleController::class, 'activate']);
});
