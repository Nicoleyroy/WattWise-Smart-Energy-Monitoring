<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlyElectricityBill;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MonthlyElectricityBillController extends Controller
{
    public function show(int $deviceId)
    {
        $billingMonth = $this->billingMonthKey();
        $bill = MonthlyElectricityBill::where('device_id', $deviceId)
            ->where('billing_month', $billingMonth)
            ->first();

        return response()->json(['data' => $bill]);
    }

    public function store(Request $request, int $deviceId)
    {
        $validated = $request->validate([
            'baseline_kwh' => ['required', 'numeric', 'min:0'],
            'rate_per_kwh' => ['required', 'numeric', 'min:0'],
        ]);

        $deviceId = (int) $deviceId;
        $billingMonth = $this->billingMonthKey();

        // Match MongoDB's BSON date value exactly before saving. Using a date
        // string here can miss the existing indexed document and cause a
        // duplicate-key insert instead of an update.
        $bill = MonthlyElectricityBill::where('device_id', $deviceId)
            ->where('billing_month', $billingMonth)
            ->first();

        if (!$bill) {
            $bill = new MonthlyElectricityBill([
                'device_id' => $deviceId,
                'billing_month' => $billingMonth,
            ]);
        }

        $bill->fill($validated);
        $bill->save();

        return response()->json([
            'message' => 'Monthly bill details saved.',
            'data' => $bill,
        ]);
    }

    private function billingMonthKey(): Carbon
    {
        $manila = Carbon::now('Asia/Manila');

        // Store the first day at UTC midnight consistently. MongoDB's unique
        // index compares the BSON date value, not the displayed timezone.
        return Carbon::create($manila->year, $manila->month, 1, 0, 0, 0, 'UTC');
    }
}
