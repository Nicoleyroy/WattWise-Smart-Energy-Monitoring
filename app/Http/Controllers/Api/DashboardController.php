<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IoTService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected IoTService $iotService;

    public function __construct(IoTService $iotService)
    {
        $this->iotService = $iotService;
    }

    /**
     * Get all dashboard data
     */
    public function getDashboardData(): JsonResponse
    {
        try {
            $data = [
                'summary' => $this->iotService->getSummaryData(),
                'ports' => $this->iotService->getAllPorts(),
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch dashboard data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle port ON/OFF
     */
    public function togglePort(Request $request, int $portId): JsonResponse
    {
        $request->validate(['state' => 'required|boolean']);

        try {
            $success = $this->iotService->togglePort($portId, $request->boolean('state'));
            return response()->json(['success' => $success]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
      * Set daily energy limit for a device. The route name is kept for
     * compatibility, but the value is now kWh, not Watts.
    
     */
    public function setThreshold(Request $request, int $deviceId): JsonResponse
    {
     
        $request->validate([
            'daily_limit' => 'nullable|numeric|min:0',
            'threshold_value' => 'nullable|numeric|min:0',
            'threshold_type' => 'nullable|in:daily,weekly,monthly',
            'threshold' => 'nullable|numeric|min:0',
        ]);

        $dailyLimit = $request->input('threshold_value', $request->input('daily_limit', $request->input('threshold')));
        $thresholdType = $request->input('threshold_type', 'daily');

        if ($dailyLimit === null) {
            return response()->json(['error' => 'threshold_value is required'], 422);
        }

        try {
            $success = $this->iotService->setThreshold($deviceId, (float) $dailyLimit, (string) $thresholdType);
            return response()->json([
                'success' => $success,
                'threshold_value' => (float) $dailyLimit,
                'threshold_type' => (string) $thresholdType,
                // Backward compatibility
                'daily_limit' => (float) $dailyLimit,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDeviceThreshold(int $deviceId): JsonResponse
    {
        try {
            $config = $this->iotService->getThresholdConfig($deviceId);
           return response()->json([
                'threshold_value' => (float) ($config['threshold_value'] ?? 0),
                'threshold_type' => (string) ($config['threshold_type'] ?? 'daily'),
                // Backward compatibility
                'daily_limit' => (float) ($config['threshold_value'] ?? 0),
                'threshold' => (float) ($config['threshold_value'] ?? 0),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getThresholds(): JsonResponse
    {
        try {
              return response()->json($this->iotService->getAllDailyLimits());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function setDeviceName(Request $request, int $deviceId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:1|max:80',
        ]);

        try {
            $name = trim((string) $validated['name']);
            $success = $this->iotService->setDeviceName($deviceId, $name);

            if (!$success) {
                return response()->json(['message' => 'Failed to update device name'], 500);
            }

            return response()->json([
                'success' => true,
                'device_id' => $deviceId,
                'name' => $name,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
