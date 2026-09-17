<?php

namespace App\Services;

use App\Models\EnergyReading;
use App\Models\MonthlyBillHistory;
use App\Models\MonthlyElectricityBill;
use Illuminate\Support\Carbon;
use App\Services\FirebaseService;

class MonthlyBillHistoryService
{
    public const TIMEZONE = 'Asia/Manila';
    public const AGGREGATE_DEVICE_ID = 'WATTWISE_001';

    public function __construct(private FirebaseService $firebase)
    {
    }

    public function generate(int $year, int $month, string $deviceId = self::AGGREGATE_DEVICE_ID): MonthlyBillHistory
    {
        $period = Carbon::create($year, $month, 1, 0, 0, 0, self::TIMEZONE);
        $start = $period->copy()->startOfMonth();
        $end = $period->copy()->endOfMonth();
        $daily = [];
        $plugTotals = [];

        foreach ([1, 2] as $plugNumber) {
            $consumption = $this->calculatePlugConsumption($plugNumber, $start, $end);
            $plugTotals[$plugNumber] = $consumption['total'];

            foreach ($consumption['daily'] as $date => $kwh) {
                $daily[$date] ??= ['date' => $date, 'plug1_kwh' => 0.0, 'plug2_kwh' => 0.0, 'total_kwh' => 0.0];
                $daily[$date]["plug{$plugNumber}_kwh"] = round($kwh, 6);
                $daily[$date]['total_kwh'] = round($daily[$date]['plug1_kwh'] + $daily[$date]['plug2_kwh'], 6);
            }
        }

        $plug1Kwh = round($plugTotals[1] ?? 0, 6);
        $plug2Kwh = round($plugTotals[2] ?? 0, 6);

        // Keep the open/current month identical to Dashboard, which uses Firebase's
        // live monthly counters. Completed months remain based on MongoDB history.
        $currentMonth = Carbon::now(self::TIMEZONE);
        if ($year === $currentMonth->year && $month === $currentMonth->month) {
            $firebaseTotals = $this->currentFirebaseTotals();
            $plug1Kwh = $firebaseTotals[1] ?? $plug1Kwh;
            $plug2Kwh = $firebaseTotals[2] ?? $plug2Kwh;
        }

        $totalKwh = round($plug1Kwh + $plug2Kwh, 6);
        $rate = $this->resolveRate($period, $start, $end);
        $estimatedCost = round($totalKwh * $rate, 2);

        ksort($daily);
        $dailyRecords = array_values($daily);
        $dailyRecords = array_map(function (array $record): array {
            $record['plug1_kwh'] = round((float) $record['plug1_kwh'], 6);
            $record['plug2_kwh'] = round((float) $record['plug2_kwh'], 6);
            $record['total_kwh'] = round((float) $record['total_kwh'], 6);
            return $record;
        }, $dailyRecords);

        return MonthlyBillHistory::updateOrCreate(
            ['device_id' => $deviceId, 'year' => $year, 'month' => $month],
            [
                'plug1_kwh' => $plug1Kwh,
                'plug2_kwh' => $plug2Kwh,
                'total_kwh' => $totalKwh,
                'rate_per_kwh' => $rate,
                'estimated_cost' => $estimatedCost,
                'daily_records' => $dailyRecords,
                'generated_at' => Carbon::now(self::TIMEZONE),
            ],
        );
    }

    public function history(string $deviceId = self::AGGREGATE_DEVICE_ID, int $limit = 24)
    {
        return MonthlyBillHistory::where('device_id', $deviceId)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit($limit)
            ->get();
    }

    private function calculatePlugConsumption(int $plugNumber, Carbon $start, Carbon $end): array
    {
        $patterns = [(string) $plugNumber, "PLUG{$plugNumber}", "plug{$plugNumber}"];
        $fields = ['_id', 'device_id', 'energy', 'created_at', 'sampled_at'];
        $before = EnergyReading::query()
            ->whereIn('device_id', $patterns)
            ->where('created_at', '<', $start)
            ->orderBy('created_at', 'desc')
            ->first($fields);

        $readings = EnergyReading::query()
            ->whereIn('device_id', $patterns)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end])
                    ->orWhereBetween('sampled_at', [$start, $end]);
            })
            ->orderBy('created_at', 'asc')
            ->get($fields);

        $ordered = collect($before ? [$before] : [])->merge($readings)
            ->sortBy(fn ($reading) => $this->readingTime($reading)->getTimestamp())
            ->values();
        $previousEnergy = null;
        $daily = [];

        foreach ($ordered as $reading) {
            $energy = (float) ($reading->energy ?? 0);
            $timestamp = $this->readingTime($reading);
            if ($previousEnergy === null) {
                $previousEnergy = $energy;
                continue;
            }

            $delta = $energy - $previousEnergy;
            $previousEnergy = $energy;
            if ($timestamp->lt($start) || $timestamp->gt($end)) {
                continue;
            }

            // A lower value indicates a meter reset; count the post-reset value as the interval usage.
            $intervalKwh = $delta >= 0 ? $delta : max(0, $energy);
            $date = $timestamp->setTimezone(self::TIMEZONE)->toDateString();
            $daily[$date] = ($daily[$date] ?? 0) + $intervalKwh;
        }

        return ['total' => array_sum($daily), 'daily' => $daily];
    }

    private function resolveRate(Carbon $period, Carbon $start, Carbon $end): float
    {
        $rates = MonthlyElectricityBill::query()
            ->whereIn('device_id', ['1', '2', 'PLUG1', 'PLUG2', 1, 2])
            ->whereDate('billing_month', '<=', $period->toDateString())
            ->orderBy('billing_month', 'desc')
            ->get(['device_id', 'rate_per_kwh', 'billing_month']);

        foreach ($rates as $bill) {
            if ((float) $bill->rate_per_kwh > 0) {
                return (float) $bill->rate_per_kwh;
            }
        }

        return 0.0;
    }

    private function currentFirebaseTotals(): array
    {
        $energy = $this->firebase->getAllEnergyData();
        $plugs = $this->firebase->getAllPlugData();
        $totals = [];

        foreach ([1, 2] as $plugNumber) {
            $energyValue = $energy["PLUG{$plugNumber}"]['monthly_kwh'] ?? null;
            $plugValue = $plugs["plug{$plugNumber}"]['monthly_kwh'] ?? null;
            $value = is_numeric($energyValue) ? $energyValue : $plugValue;
            if (is_numeric($value)) {
                $totals[$plugNumber] = round(max(0, (float) $value), 6);
            }
        }

        return $totals;
    }

    private function readingTime($reading): Carbon
    {
        $timestamp = $reading->sampled_at ?? $reading->created_at;
        return Carbon::parse($timestamp)->setTimezone(self::TIMEZONE);
    }
}
