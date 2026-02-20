<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnergyReading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IoTController extends Controller
{
    /**
     * Receive energy data from ESP32
     * 
     * Expected JSON format from ESP32:
     * {
     *   "voltage": 220.5,
     *   "current": 1.2,
     *   "power": 264.6,
     *   "energy": 0.0735,
     *   "frequency": 50.0,
     *   "pf": 0.85,
     *   "device_id": "ESP32_001"
     * }
     */
    public function storeEnergyReading(Request $request)
    {
        // Log incoming request for debugging
        Log::info('ESP32 Energy Reading Received', [
            'ip' => $request->ip(),
            'data' => $request->all()
        ]);

        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'voltage' => 'nullable|numeric|min:0|max:9999.99',
            'current' => 'nullable|numeric|min:0|max:9999.99',
            'power' => 'nullable|numeric|min:0|max:99999999.99',
            'energy' => 'nullable|numeric|min:0|max:999999.9999',
            'frequency' => 'nullable|numeric|min:0|max:9999.99',
            'pf' => 'nullable|numeric|min:0|max:1',
            'device_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            Log::warning('ESP32 Data Validation Failed', [
                'errors' => $validator->errors(),
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store the reading
            $reading = EnergyReading::create([
                'voltage' => $request->input('voltage'),
                'current' => $request->input('current'),
                'power' => $request->input('power'),
                'energy' => $request->input('energy'),
                'frequency' => $request->input('frequency'),
                'power_factor' => $request->input('pf'), // Note: ESP32 sends 'pf', we store as 'power_factor'
                'device_id' => $request->input('device_id', 'unknown'),
            ]);

            Log::info('ESP32 Energy Reading Stored', [
                'id' => $reading->id,
                'device_id' => $reading->device_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Energy reading stored successfully',
                'data' => [
                    'id' => $reading->id,
                    'timestamp' => $reading->created_at->toIso8601String()
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('ESP32 Energy Reading Storage Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to store energy reading'
            ], 500);
        }
    }

    /**
     * Get latest energy readings
     */
    public function getLatestReadings(Request $request)
    {
        $limit = $request->input('limit', 10);
        $deviceId = $request->input('device_id');

        $query = EnergyReading::query()
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $readings = $query->get();

        return response()->json([
            'success' => true,
            'data' => $readings
        ]);
    }

    /**
     * Get energy statistics
     */
    public function getStatistics(Request $request)
    {
        $deviceId = $request->input('device_id');
        $hours = $request->input('hours', 24);

        $query = EnergyReading::query()
            ->where('created_at', '>=', now()->subHours($hours));

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $stats = [
            'avg_voltage' => $query->avg('voltage'),
            'avg_current' => $query->avg('current'),
            'avg_power' => $query->avg('power'),
            'total_energy' => $query->sum('energy'),
            'avg_frequency' => $query->avg('frequency'),
            'avg_power_factor' => $query->avg('power_factor'),
            'count' => $query->count(),
        ];

        return response()->json([
            'success' => true,
            'period_hours' => $hours,
            'data' => $stats
        ]);
    }
}
