<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceAlert;
use App\Notifications\DeviceAlertNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenanceAlertController extends Controller
{
    /**
     * Get all maintenance alerts (newest first)
     */
    public function index(): JsonResponse
    {
        $alerts = MaintenanceAlert::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Create a new maintenance alert
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
            'plug_number' => 'required|integer',
            'message' => 'required|string',
            'status' => 'required|in:warning,critical',
        ]);

        $alert = MaintenanceAlert::create($request->only([
            'device_id', 'plug_number', 'message', 'status',
        ]));

        return response()->json([
            'success' => true,
            'data' => $alert,
        ], 201);
    }

    /**
     * Delete a maintenance alert
     */
    public function destroy(int $id): JsonResponse
    {
        $alert = MaintenanceAlert::findOrFail($id);
        $alert->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Mark a maintenance alert as read
     */
    public function markAsRead(int $id): JsonResponse
    {
        $alert = MaintenanceAlert::findOrFail($id);
        $alert->update(['is_read' => true]);

        return response()->json(['success' => true, 'data' => $alert]);
    }

    /**
    * Trigger a daily energy limit alert and notification
      */
    public function triggerThresholdAlert(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required',
            'device_name' => 'required|string',
            'daily_kwh' => 'required|numeric',
            'daily_limit' => 'required|numeric',
       ]);

        $deviceId = $request->device_id;
        $deviceName = $request->device_name;
        $dailyKwh = (float) $request->daily_kwh;
        $dailyLimit = (float) $request->daily_limit;

        // 1. Create Maintenance Alert for Dashboard
        $alert = MaintenanceAlert::create([
            'device_id' => $deviceId,
            'plug_number' => (int)$deviceId, // Assuming device_id maps to plug number for simplicity
        
             'message' => "CRITICAL: {$deviceName} reached its daily energy limit ({$dailyKwh} / {$dailyLimit} kWh) and was turned off.",
              'status' => 'critical',
        ]);

        // 2. Send Laravel Notification
        $user = Auth::user();
        if ($user) {
            $user->notify(new DeviceAlertNotification([
                'title' => 'Daily Energy Limit Reached',
                'message' => "Your device '{$deviceName}' used {$dailyKwh} / {$dailyLimit} kWh today and was turned off.",
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'severity' => 'critical',
                'type' => 'alert',
                'action' => "/device/{$deviceId}/settings"
            ]));
        }

        return response()->json([
            'success' => true,
            'alert' => $alert
        ]);
    }
}
