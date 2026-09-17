<?php

namespace App\Console\Commands;

use App\Services\MonthlyBillHistoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateMonthlyBillHistory extends Command
{
    protected $signature = 'wattwise:generate-monthly-bill
        {--year= : Billing year, defaults to the previous month}
        {--month= : Billing month number, defaults to the previous month}
        {--device=WATTWISE_001 : Aggregate device identifier}';

    protected $description = 'Generate or regenerate a MongoDB monthly bill history summary';

    public function handle(MonthlyBillHistoryService $service): int
    {
        $target = Carbon::now(MonthlyBillHistoryService::TIMEZONE)->subMonth();
        $year = (int) ($this->option('year') ?: $target->year);
        $month = (int) ($this->option('month') ?: $target->month);

        if ($month < 1 || $month > 12) {
            $this->error('The month must be between 1 and 12.');
            return self::FAILURE;
        }

        $summary = $service->generate($year, $month, (string) $this->option('device'));
        $this->info(sprintf(
            'Generated %04d-%02d: %.3f kWh at PHP %.4f/kWh = PHP %.2f (%d daily records).',
            $year,
            $month,
            $summary->total_kwh,
            $summary->rate_per_kwh,
            $summary->estimated_cost,
            count($summary->daily_records ?? []),
        ));

        return self::SUCCESS;
    }
}
