<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnergyReading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\FirebaseService;


class IoTController extends Controller
{
    protected FirebaseService $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    /**
     * Receive energy data from ESP32
     * 
     * Expected JSON format from ESP32:
     * {
     *   "voltage": 220.5,
     *   "current": 1.2,
     *   "power": 264.6,
     *   "energy": 0.0735,
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
            $deviceId = $request->input('device_id', 'unknown');
            $latestReading = EnergyReading::where('device_id', $deviceId)
                ->latest('created_at')
                ->first();

            if ($latestReading && $latestReading->created_at->gt(now()->subSeconds(60))) {
                return response()->json([
                    'success' => true,
                    'stored' => false,
                    'message' => 'Reading skipped; a reading was already stored within the last 60 seconds.',
                    'data' => [
                        'id' => $latestReading->id,
                        'timestamp' => $latestReading->created_at->toIso8601String(),
                    ],
                ]);
            }

            // Store the reading
            $reading = EnergyReading::create([
                'voltage' => $request->input('voltage'),
                'current' => $request->input('current'),
                'power' => $request->input('power'),
                'energy' => $request->input('energy'),
                'device_id' => $deviceId,
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
     * Get historical energy readings for charts (sampled)
     */
    public function getHistory(Request $request)
    {
        $deviceId = $request->input('device_id');
        $hours = (int) $request->input('hours', 1);
        $intervalMinutes = max(0, (int) $request->input('interval_minutes', 0));

        // Ensure we handle both numeric IDs or PLUG1 format
        $deviceIdPatterns = $this->buildDeviceIdPatterns($deviceId);

        $query = EnergyReading::query()
            ->whereIn('device_id', $deviceIdPatterns)
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'asc');

        $readings = $query->get();

        if ($intervalMinutes > 0) {
            $intervalSeconds = $intervalMinutes * 60;
            $readings = $readings->unique(function ($reading) use ($intervalSeconds) {
                $timestamp = $reading->created_at->getTimestamp();
                return intdiv($timestamp, $intervalSeconds);
            })->values();
        }

        // Sample data to max 60 points for the chart to keep it performant
        if ($intervalMinutes === 0 && $readings->count() > 60) {
            $count = $readings->count();
            $step = floor($count / 60);
            $sampled = [];
            for ($i = 0; $i < $count; $i += $step) {
                $sampled[] = $readings[$i];
                if (count($sampled) >= 60) break;
            }
            $readings = collect($sampled);
        }

        return response()->json([
            'success' => true,
            'count' => $readings->count(),
            'period_hours' => $hours,
            'data' => $readings->map(function($r) {
                return [
                    'voltage' => (float)$r->voltage,
                    'current' => (float)$r->current,
                    'power'   => (float)$r->power,
                    'energy'  => (float)$r->energy,
                    'timestamp' => $r->created_at->format('H:i:s'),
                    'full_timestamp' => $r->created_at->toIso8601String(),
                ];
            })
        ]);
    }

    public function clearEnergyData(int $deviceId)
    {
        $deviceIdPatterns = $this->buildDeviceIdPatterns((string) $deviceId);
        $deleted = EnergyReading::whereIn('device_id', $deviceIdPatterns)->delete();

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => 'Energy readings cleared successfully.',
        ]);
    }

    /**
     * Export device energy readings by date range as CSV.
     */
    public function exportEnergyData(Request $request, int $deviceId)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = now()->parse($validated['start_date'])->startOfDay();
        $end = now()->parse($validated['end_date'])->endOfDay();
        $deviceIdPatterns = $this->buildDeviceIdPatterns((string) $deviceId);

        $rows = EnergyReading::query()
            ->whereIn('device_id', $deviceIdPatterns)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'asc')
            ->get([
                'created_at',
                'device_id',
                'voltage',
                'current',
                'power',
                'energy',
            ]);

        $rows = $rows->unique(function ($row) {
            return intdiv($row->created_at->getTimestamp(), 30 * 60);
        })->values();

        if ($rows->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No energy data found for the selected date range.',
            ], 404);
        }

        $filename = "device_{$deviceId}_energy_{$start->format('Ymd')}_to_{$end->format('Ymd')}.csv";

        return response()->streamDownload(function () use ($rows, $deviceId, $start, $end) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Report', 'Energy Data Export']);
            fputcsv($out, ['Device ID', "PLUG{$deviceId}"]);
            fputcsv($out, ['Date Range', $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d')]);
            fputcsv($out, ['Exported At', now()->toIso8601String()]);
            fputcsv($out, []);

            fputcsv($out, [
                'timestamp',
                'device_id',
                'voltage',
                'current',
                'power',
                'energy',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    optional($row->created_at)->toIso8601String(),
                    (string) $row->device_id,
                    $row->voltage,
                    $row->current,
                    $row->power,
                    $row->energy,
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Footer']);
            fputcsv($out, ['Total Records', $rows->count()]);

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $request->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    /**
     * Delete notification
     */
    public function deleteNotification(Request $request, $id)
    {
        $request->user()->notifications()->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    protected function buildDeviceIdPatterns(?string $deviceId): array
    {
        $value = strtoupper(trim((string) $deviceId));
        $patterns = [];

        if ($value !== '') {
            $patterns[] = $value;
        }

        if (preg_match('/PLUG(\d+)/', $value, $matches)) {
            $num = (string) ((int) $matches[1]);
            $patterns[] = $num;
            $patterns[] = "PLUG{$num}";
        } elseif (is_numeric($value)) {
            $num = (string) ((int) $value);
            $patterns[] = $num;
            $patterns[] = "PLUG{$num}";
        }

        if (empty($patterns)) {
            return ['PLUG1'];
        }

        return array_values(array_unique($patterns));
    }
}
