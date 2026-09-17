<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IoTService
{
    protected FirebaseService $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    /**
     * Get summary data for dashboard
     */
    public function getSummaryData(): array
    {
        $liveData = $this->cachedLiveData();
        $plugData = $this->cachedPlugData();
        $totalPower = 0;
        $totalEnergy = 0;

        foreach ($liveData as $deviceData) {
            $totalPower += $deviceData['power'] ?? 0;
          
        }

         foreach ($plugData as $plug) {
            $totalEnergy += $plug['daily_kwh'] ?? 0;
        }
        
        return [
            'current_power' => ['value' => round($totalPower, 1), 'unit' => 'W'],
            'today_usage' => ['value' => round($totalEnergy, 2), 'unit' => 'kWh'],
        ];
    }

    /**
     * Get all ports from Firebase
     */
    public function getAllPorts(): array
    {
        $liveData = $this->cachedLiveData();
        $controls = $this->cachedControls();
        $plugData = $this->cachedPlugData();
    

        $ports = [];
        foreach ($liveData as $deviceKey => $deviceData) {
            if (preg_match('/PLUG(\d+)/', $deviceKey, $matches)) {
                $deviceId = (int)$matches[1];
                $plug = $plugData["plug{$deviceId}"] ?? [];
                $ports[] = [
                    'id' => $deviceId,
                    'name' => $plug['name'] ?? "Port {$deviceId}",
                    'is_on' => $controls[$deviceKey] ?? false,
                    'voltage' => $deviceData['voltage'] ?? 0,
                    'current' => $deviceData['current'] ?? 0,
                    'power' => $plug['current_power'] ?? $deviceData['power'] ?? 0,
                    'energy' => $plug['daily_kwh'] ?? 0,
                    'threshold' => $plug['daily_limit'] ?? 0,
                    'status' => ($deviceData['anomaly'] ?? false) ? 'Check Soon' : 'Healthy',
                    'status_tone' => ($deviceData['anomaly'] ?? false) ? 'warning' : 'healthy',
                ];
            }
        }

        return $ports;
    }

    public function togglePort(int $portId, bool $state): bool
    {
        $success = $this->firebase->setControl($portId, $state);

        if ($success) {
            Cache::forget('iot.controls');
        }

        return $success;
    }

    private function cachedLiveData(): array
    {
        return Cache::remember('iot.live_data', now()->addSeconds(3), fn (): array => $this->firebase->getAllLiveData());
    }

    private function cachedPlugData(): array
    {
        return Cache::remember('iot.plug_data', now()->addSeconds(3), fn (): array => $this->firebase->getAllPlugData());
    }

    private function cachedControls(): array
    {
        return Cache::remember('iot.controls', now()->addSeconds(3), fn (): array => $this->firebase->getAllControls());
    }

    public function setThreshold(int $deviceId, float $threshold, string $thresholdType = 'daily'): bool
    {
        return $this->firebase->setThresholdConfig($deviceId, $threshold, $thresholdType);
    }

    public function getDailyLimit(int $deviceId): ?float
    {
        return $this->firebase->getDailyLimit($deviceId);
    }

    public function getThresholdConfig(int $deviceId): array
    {
        return $this->firebase->getThresholdConfig($deviceId);
    }

    public function getAllDailyLimits(): array
    {
        return $this->firebase->getAllThresholds();
    }

    public function setDeviceName(int $deviceId, string $name): bool
    {
        return $this->firebase->setDeviceName($deviceId, $name);
    }
}
