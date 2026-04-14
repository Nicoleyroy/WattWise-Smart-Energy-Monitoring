<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnergyBudget;
use App\Models\EnergyReading;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EnergyBudgetController extends Controller
{
    /**
     * Get the current budget and its status for a specific device
     */
    public function status(Request $request, string $deviceId)
    {
        $budget = EnergyBudget::where('device_id', $deviceId)->first();
        
        if (!$budget) {
            return response()->json([
                'success' => true,
                'has_budget' => false,
            ]);
        }

        // Patterns to match the device_id robustly
        $deviceIdPatterns = [$deviceId];
        if (is_numeric($deviceId)) {
            $deviceIdPatterns[] = "PLUG{$deviceId}";
        } elseif (preg_match('/PLUG(\d+)/', $deviceId, $matches)) {
            $deviceIdPatterns[] = $matches[1];
        }

        $now = Carbon::now();
        $start = null;
        $end = null;

        if ($budget->threshold_type === 'daily') {
            $start = $now->copy()->startOfDay();
            $end = $now->copy()->endOfDay();
        } elseif ($budget->threshold_type === 'weekly') {
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfWeek();
        } elseif ($budget->threshold_type === 'monthly') {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
        }

        $usage = EnergyReading::whereIn('device_id', $deviceIdPatterns)
            ->whereBetween('created_at', [$start, $end])
            ->sum('energy');

        $usage = (float) $usage;
        $threshold = (float) $budget->threshold_value;
        $percentage = $threshold > 0 ? ($usage / $threshold) * 100 : 0;
        
        $status = "Normal";
        if ($usage >= $threshold) {
            $status = "Exceeded";
        } elseif ($percentage >= 90) {
            $status = "Critical";
        } elseif ($percentage >= 70) {
            $status = "Warning";
        }

        return response()->json([
            'success' => true,
            'has_budget' => true,
            'budget' => $budget,
            'usage' => round($usage, 4),
            'threshold' => $threshold,
            'percentage' => round($percentage, 1),
            'status' => $status,
            'period_start' => $start->toIso8601String(),
            'period_end' => $end->toIso8601String(),
        ]);
    }

    /**
     * Create or update the active threshold limit for a device
     */
    public function store(Request $request, string $deviceId)
    {
        $request->validate([
            'threshold_value' => 'required|numeric|min:0.01',
            'threshold_type' => 'required|in:daily,weekly,monthly',
        ]);

        $budget = EnergyBudget::updateOrCreate(
            ['device_id' => $deviceId],
            [
                'threshold_value' => $request->input('threshold_value'),
                'threshold_type' => $request->input('threshold_type'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Energy budget saved successfully',
            'data' => $budget
        ]);
    }

    /**
     * Remove the energy budget for a device
     */
    public function destroy(string $deviceId)
    {
        EnergyBudget::where('device_id', $deviceId)->delete();
        return response()->json(['success' => true]);
    }
}
