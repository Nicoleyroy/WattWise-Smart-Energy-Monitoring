<?php

namespace App\Console\Commands;

use App\Models\EnergyReading;
use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncLiveEnergyToMongo extends Command
{
    protected $signature = 'iot:sync-live';

    protected $description = 'Store Firebase Live energy snapshots in MongoDB';

    public function handle(FirebaseService $firebase): int
    {
        $liveData = $firebase->getAllLiveData();

        if ($liveData === []) {
            $this->warn('No live Firebase data was available.');

            return self::SUCCESS;
        }

        $sampledAt = Carbon::now();
        $sampledAt->minute(intdiv($sampledAt->minute, 5) * 5)->second(0)->micro(0);
        $stored = 0;

        foreach ($liveData as $deviceId => $reading) {
            if (!is_array($reading)) {
                continue;
            }

            EnergyReading::updateOrCreate(
                [
                    'device_id' => (string) $deviceId,
                    'sampled_at' => $sampledAt,
                ],
                [
                    'voltage' => $this->number($reading['voltage'] ?? null),
                    'current' => $this->number($reading['current'] ?? null),
                    'power' => $this->number($reading['power'] ?? null),
                    'energy' => $this->number($reading['energy'] ?? null),
                    'frequency' => $this->number($reading['frequency'] ?? null),
                    'power_factor' => $this->number($reading['pf'] ?? $reading['power_factor'] ?? null),
                    'source' => 'firebase_live',
                ]
            );

            $stored++;
        }

        $this->info("Stored {$stored} live device snapshot(s) for {$sampledAt->toIso8601String()}.");

        return self::SUCCESS;
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

}
