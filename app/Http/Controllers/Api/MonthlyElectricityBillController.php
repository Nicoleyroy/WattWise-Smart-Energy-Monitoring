<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlyElectricityBill;
use Illuminate\Http\Request;

class MonthlyElectricityBillController extends Controller
{
    public function show(int $deviceId)
    {
        $billingMonth = now()->startOfMonth()->toDateString();
        $bill = MonthlyElectricityBill::where('device_id', $deviceId)
            ->whereDate('billing_month', $billingMonth)
            ->first();

        return response()->json(['data' => $bill]);
    }

    public function store(Request $request, int $deviceId)
    {
        $validated = $request->validate([
            'baseline_kwh' => ['required', 'numeric', 'min:0'],
            'rate_per_kwh' => ['required', 'numeric', 'min:0'],
        ]);

        $bill = MonthlyElectricityBill::updateOrCreate(
            [
                'device_id' => $deviceId,
                'billing_month' => now()->startOfMonth()->toDateString(),
            ],
            $validated,
        );

        return response()->json([
            'message' => 'Monthly bill details saved.',
            'data' => $bill,
        ]);
    }
}
