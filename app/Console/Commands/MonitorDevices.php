<?php

namespace App\Console\Commands;

use App\Services\MonitoringService;
use Illuminate\Console\Command;

class MonitorDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'devices:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Firebase for device threshold breaches and anomalies';

    /**
     * Execute the console command.
     */
    public function handle(MonitoringService $monitoringService)
    {
        $this->info('Starting device monitoring...');
        
        // In a real production environment, this would run in a loop or be scheduled
        // For development/demo, we can run it once or use a simple loop
        $monitoringService->monitor();
        
        $this->info('Monitoring cycle complete.');
    }
}
