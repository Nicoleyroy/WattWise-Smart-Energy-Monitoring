<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Illuminate\Console\Command;

class ResetEnergyCounters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'energy:reset-period {period : daily|weekly|monthly}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset Firebase energy counters by period (daily, weekly, monthly)';

    public function handle(FirebaseService $firebase): int
    {
        $period = (string) $this->argument('period');
        if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $this->error('Invalid period. Use one of: daily, weekly, monthly.');
            return self::FAILURE;
        }

        $count = $firebase->resetEnergyCounters($period);
        $this->info("Reset {$period} counters for {$count} device(s).");

        return self::SUCCESS;
    }
}
