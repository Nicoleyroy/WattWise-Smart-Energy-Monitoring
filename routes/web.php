<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Landing');
})->name('landing');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/devices', function () {
    // Mock devices data for frontend
    $devices = [
        [
            'id' => 1,
            'name' => 'Smart Refrigerator',
            'status' => 'online',
            'power' => 1.2,
            'daily_usage' => 24.5,
            'monthly_cost' => 45.80,
            'uptime' => '15d 3h',
            'category' => 'Plug 1',
            'voltage_threshold' => 230,
            'current_threshold' => 10,
            'power_threshold' => 1500
        ],
        [
            'id' => 2,
            'name' => 'Air Conditioner',
            'status' => 'online',
            'power' => 2.5,
            'daily_usage' => 18.2,
            'monthly_cost' => 72.50,
            'uptime' => '5d 12h',
            'category' => 'Plug 2',
            'voltage_threshold' => 240,
            'current_threshold' => 15,
            'power_threshold' => 3000
        ],
        [
            'id' => 3,
            'name' => 'Water Heater',
            'status' => 'standby',
            'power' => 0.8,
            'daily_usage' => 8.4,
            'monthly_cost' => 28.30,
            'uptime' => '30d 8h',
            'category' => 'Plug 3',
            'voltage_threshold' => 230,
            'current_threshold' => 20,
            'power_threshold' => 4000
        ]

    ];
    
    return Inertia::render('Devices', [
        'devices' => $devices
    ]);
})->middleware(['auth', 'verified'])->name('devices');

Route::get('/notifications', function () {
    return Inertia::render('Notifications');
})->middleware(['auth', 'verified'])->name('notifications');

require __DIR__.'/auth.php';
