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
     * Get all dashboard data (summary cards, ports, alerts)
     */
    public function getDashboardData(): JsonResponse
    {
        try {
            $data = [
                'summary' => $this->iotService->getSummaryData(),
                'ports' => $this->iotService->getAllPorts(),
                'alerts' => $this->iotService->getAlerts(),
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
     * Get all ports/devices
     */
    public function getPorts(): JsonResponse
    {
        try {
            $ports = $this->iotService->getAllPorts();
            return response()->json($ports);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch ports',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle port ON/OFF
     */
    public function togglePort(Request $request, int $portId): JsonResponse
    {
        $request->validate([
            'state' => 'required|boolean',
        ]);

        try {
            $result = $this->iotService->togglePort($portId, $request->boolean('state'));
            
            return response()->json([
                'success' => true,
                'port' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to toggle port',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific port status
     */
    public function getPortStatus(int $portId): JsonResponse
    {
        try {
            $port = $this->iotService->getPortStatus($portId);
            return response()->json($port);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch port status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current total power consumption
     */
    public function getCurrentPower(): JsonResponse
    {
        try {
            $power = $this->iotService->getCurrentPower();
            return response()->json(['power' => $power]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch current power',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get today's energy usage
     */
    public function getTodayUsage(): JsonResponse
    {
        try {
            $usage = $this->iotService->getTodayUsage();
            return response()->json(['usage' => $usage]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch today usage',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get maintenance alerts
     */
    public function getAlerts(): JsonResponse
    {
        try {
            $alerts = $this->iotService->getAlerts();
            return response()->json($alerts);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch alerts',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get monthly energy records
     */
    public function getMonthlyRecords(): JsonResponse
    {
        try {
            $records = $this->iotService->getMonthlyRecords();
            return response()->json($records);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch monthly records',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active thresholds
     */
    public function getThresholds(): JsonResponse
    {
        try {
            $thresholds = $this->iotService->getThresholds();
            return response()->json($thresholds);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch thresholds',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

