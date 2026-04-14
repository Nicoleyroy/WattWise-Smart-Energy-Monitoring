<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BackupController;
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

Route::get('/device/{id}/settings', function ($id) {
    // Mock device data - replace with actual database query
    $devices = [
        1 => ['id' => 1, 'name' => 'Plug 1'],
        2 => ['id' => 2, 'name' => 'Plug 2'],
        3 => ['id' => 3, 'name' => 'Plug 3'],
    ];
    
    $device = $devices[$id] ?? ['id' => $id, 'name' => 'Plug ' . $id];
    
    return Inertia::render('DeviceSettings', [
        'device' => $device
    ]);
})->middleware(['auth', 'verified'])->name('device.settings');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/backup', [BackupController::class, 'download'])->name('profile.backup');
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
            'daily_limit' => 30.0,
            'category' => 'Plug 1'
        ],
        [
            'id' => 2,
            'name' => 'Air Conditioner',
            'status' => 'online',
            'power' => 2.5,
            'daily_usage' => 18.2,
            'monthly_cost' => 72.50,
            'uptime' => '5d 12h',
            'daily_limit' => 25.0,
            'category' => 'Plug 2'
        ]
    ];
    
    return Inertia::render('Devices', [
        'devices' => $devices
    ]);
})->middleware(['auth', 'verified'])->name('devices');

Route::get('/notifications', function () {
    $notifications = [];
    if (auth()->check()) {
        $notifications = auth()->user()->notifications()->orderBy('created_at', 'desc')->get()->map(function($n) {
            return [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'info',
                'severity' => $n->data['severity'] ?? 'info',
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'device' => $n->data['device_name'] ?? null,
                'time' => $n->created_at->diffForHumans(),
                'timestamp' => $n->created_at,
                'read' => !is_null($n->read_at),
                'action' => $n->data['action'] ?? null,
            ];
        });
    }

    return Inertia::render('Notifications', [
        'initialNotifications' => $notifications
    ]);
})->middleware(['auth', 'verified'])->name('notifications');

Route::get('/definitions', function () {
    return Inertia::render('Definitions');
})->middleware(['auth', 'verified'])->name('definitions');

require __DIR__.'/auth.php';
