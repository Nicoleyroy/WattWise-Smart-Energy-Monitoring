<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\FirebaseService;

class SimulateDeviceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:simulate {--plug=1 : The Plug ID} {--count=1 : Number of readings} {--anomaly : Simulate a general anomaly} {--limit : Simulate daily energy limit reached} {--voltage-low : Simulate under-voltage (<200V)} {--voltage-high : Simulate over-voltage (>260V)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate ESP32 sending data to Firebase and backend for testing';

    public function handle(FirebaseService $firebase)
    {
        $deviceId = (int) $this->option('plug');
        $count = (int) $this->option('count');
        $anomaly = $this->option('anomaly');
        $dailyLimitReached = $this->option('limit');
        $voltageLow = $this->option('voltage-low');
        $voltageHigh = $this->option('voltage-high');

        $this->info("Simulating dummy data for PLUG{$deviceId}...");

        $dailyLimit = $firebase->getDailyLimit($deviceId) ?? 0;

        $dbUrl = rtrim(config('iot.firebase.database_url', env('FIREBASE_DATABASE_URL', '')), '/');
        $apiKey = config('iot.firebase.api_key', env('FIREBASE_API_KEY'));
        $auth = $apiKey ? "?auth={$apiKey}" : "";

        if (empty($dbUrl)) {
            $this->error("Firebase Database URL is missing in the configuration.");
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            $voltage = rand(210, 230);
            
            if ($voltageLow) {
                $voltage = rand(150, 190);
            } elseif ($voltageHigh) {
                $voltage = rand(265, 280);
            }
            
            $current = rand(10, 50) / 10;
           
            
            $power = round($voltage * $current, 2);
            $energy = round($power * 0.001, 4);
             $dailyKwh = $dailyLimitReached && $dailyLimit > 0 ? $dailyLimit : $energy;
            $frequency = 60 + (rand(-10, 10) / 100);
            $pf = 0.85 + (rand(0, 10) / 100);
            $optimal = round($pf * 100, 1);

            $liveData = [
                'voltage' => round($voltage, 2),
                'current' => round($current, 2),
                'power'   => $power,
                'energy'  => $energy,
                'frequency' => $frequency,
                'pf'      => $pf,
                'optimal' => $optimal,
                'anomaly' => $anomaly
            ];

            // 1. Update Firebase Live
            $responseLive = Http::put("{$dbUrl}/Live/PLUG{$deviceId}.json{$auth}", $liveData);
            if ($responseLive->successful()) {
                $this->info("✓ Updated Firebase Live: {$power}W, {$current}A, {$voltage}V (Anomaly: " . ($anomaly ? 'Yes' : 'No') . ")");
            } else {
                 $this->error("Failed to update Firebase Live.");
            }

            // 2. Update Firebase History for Graphs
            $timestamp = time();
            $historyPayload = [
                "PLUG{$deviceId}" => [
                   'power' => $power,
                   'voltage' => round($voltage, 2),
                   'current' => round($current, 2),
                   'frequency' => $frequency,
                   'pf' => $pf,
                   'energy' => $energy
                ]
            ];
            Http::patch("{$dbUrl}/History/{$timestamp}.json{$auth}", $historyPayload);

             $isPlugOn = !$dailyLimitReached;
             $nowMs = (int) (microtime(true) * 1000);
             if ($isPlugOn) {
                 $firebase->setDeviceUptime($deviceId, $nowMs - 3600000); // 1 hour uptime simulated
             } else {
                 $firebase->setDeviceUptime($deviceId, null);
             }

             Http::patch("{$dbUrl}/plugs/plug{$deviceId}.json{$auth}", [
                'current_power' => $power,
                'daily_kwh' => $dailyKwh,
                'daily_limit' => $dailyLimit,
                'status' => $dailyLimitReached ? 'OFF' : 'ON',
                'last_updated' => $timestamp,
            ]);

            // 3. POST to Laravel for records
           
            $apiUrl = url('/api/iot/energy');
            try {
                $response = Http::post($apiUrl, [
                    'voltage'   => $liveData['voltage'],
                    'current'   => $liveData['current'],
                    'power'     => $liveData['power'],
                    'energy'    => $liveData['energy'],
                    'frequency' => $frequency,
                    'pf'        => $pf,
                    'device_id' => "PLUG{$deviceId}"
                ]);

                if ($response->successful()) {
                    $this->info("✓ Successfully POSTed reading to API");
                } else {
                    $this->error("✗ API POST Failed: " . $response->body());
                }
            } catch (\Exception $e) {
                $this->error("Could not reach API: " . $e->getMessage());
            }

            // 4. Trigger anomaly monitor if necessary 
            $this->info("Triggering Anomaly Monitor command...");
            $this->call('devices:monitor');

            if ($i < $count - 1) {
                sleep(2);
            }
        }

        $this->info("\nSimulation completed successfully! Check your dashboard/device settings for the updates.");
    }
}
