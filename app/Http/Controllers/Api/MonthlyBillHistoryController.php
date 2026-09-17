<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlyBillHistory;
use App\Services\MonthlyBillHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MonthlyBillHistoryController extends Controller
{
    public function __construct(private MonthlyBillHistoryService $service)
    {
    }

    public function index(Request $request)
    {
        $deviceId = (string) $request->input('device_id', MonthlyBillHistoryService::AGGREGATE_DEVICE_ID);
        [$year, $month] = $this->selectedPeriod($request->input('month'));
        $selected = $this->service->generate($year, $month, $deviceId);
        $history = $this->service->history($deviceId);
        $previous = $this->previousSummary($year, $month, $deviceId);

        return response()->json([
            'data' => $this->formatSummary($selected, $previous),
            'history' => $history->map(fn ($summary) => $this->formatSummary($summary, $this->previousSummary($summary->year, $summary->month, $deviceId)))->values(),
            'timezone' => MonthlyBillHistoryService::TIMEZONE,
        ]);
    }

    public function exportCsv(Request $request)
    {
        $deviceId = (string) $request->input('device_id', MonthlyBillHistoryService::AGGREGATE_DEVICE_ID);
        [$year, $month] = $this->selectedPeriod($request->input('month'));
        $summary = $this->service->generate($year, $month, $deviceId);
        $monthLabel = Carbon::create($year, $month, 1, 0, 0, 0, MonthlyBillHistoryService::TIMEZONE)->format('F Y');
        $filename = "monthly_bill_history_{$year}_" . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.csv';

        return response()->streamDownload(function () use ($summary, $deviceId, $monthLabel) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Report', 'Estimated Monthly Bill History']);
            fputcsv($out, ['Device ID', $deviceId]);
            fputcsv($out, ['Month', $monthLabel]);
            fputcsv($out, ['Estimated Monthly Bill', number_format((float) $summary->estimated_cost, 2, '.', '')]);
            fputcsv($out, ['Electricity Rate (PHP/kWh)', number_format((float) $summary->rate_per_kwh, 4, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['date', 'plug1_kwh', 'plug2_kwh', 'total_kwh', 'device_id', 'month', 'rate_per_kwh', 'estimated_cost']);

            foreach ($summary->daily_records ?? [] as $daily) {
                fputcsv($out, [
                    $daily['date'] ?? '',
                    $daily['plug1_kwh'] ?? 0,
                    $daily['plug2_kwh'] ?? 0,
                    $daily['total_kwh'] ?? 0,
                    $deviceId,
                    $monthLabel,
                    $summary->rate_per_kwh,
                    $summary->estimated_cost,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function selectedPeriod(?string $value): array
    {
        if ($value && preg_match('/^(\d{4})-(\d{2})$/', $value, $matches)) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        $now = Carbon::now(MonthlyBillHistoryService::TIMEZONE);
        return [$now->year, $now->month];
    }

    private function previousSummary(int $year, int $month, string $deviceId): ?MonthlyBillHistory
    {
        $period = Carbon::create($year, $month, 1, 0, 0, 0, MonthlyBillHistoryService::TIMEZONE)->subMonth();
        return MonthlyBillHistory::where('device_id', $deviceId)
            ->where('year', $period->year)
            ->where('month', $period->month)
            ->first();
    }

    private function formatSummary(MonthlyBillHistory $summary, ?MonthlyBillHistory $previous): array
    {
        $change = null;
        if ($previous && (float) $previous->total_kwh > 0) {
            $change = round((((float) $summary->total_kwh - (float) $previous->total_kwh) / (float) $previous->total_kwh) * 100, 2);
        }

        return [
            'id' => (string) $summary->getKey(),
            'device_id' => $summary->device_id,
            'year' => (int) $summary->year,
            'month' => (int) $summary->month,
            'month_key' => sprintf('%04d-%02d', $summary->year, $summary->month),
            'plug1_kwh' => (float) $summary->plug1_kwh,
            'plug2_kwh' => (float) $summary->plug2_kwh,
            'total_kwh' => (float) $summary->total_kwh,
            'rate_per_kwh' => (float) $summary->rate_per_kwh,
            'estimated_cost' => (float) $summary->estimated_cost,
            'change_percent' => $change,
            'daily_records' => $summary->daily_records ?? [],
            'generated_at' => optional($summary->generated_at)->timezone(MonthlyBillHistoryService::TIMEZONE)->toIso8601String(),
        ];
    }
}
