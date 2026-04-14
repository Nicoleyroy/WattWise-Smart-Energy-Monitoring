<?php

namespace App\Services;

use App\Models\User;
use App\Models\MaintenanceAlert;
use App\Notifications\DeviceAlertNotification;
use App\Events\ThresholdExceeded;
use App\Events\DeviceTurnedOff;
use App\Events\DeviceOffline;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MonitoringService
{
    protected FirebaseService $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    /**
     * Monitor all devices for threshold breaches and anomalies
     */
    public function monitor(): void
    {
        try {
            $liveData = $this->firebase->getAllLiveData();
            $plugData = $this->firebase->getAllPlugData();
            $energyData = $this->firebase->getAllEnergyData();
             
            if (empty($liveData)) {
                return;
            }

            foreach ($liveData as $deviceKey => $data) {
                if (!preg_match('/PLUG(\d+)/', $deviceKey, $matches)) {
                    continue;
                }

                $deviceId = (int)$matches[1];
                $plug = $plugData["plug{$deviceId}"] ?? [];
                 $energy = $energyData["PLUG{$deviceId}"] ?? [];
                
                 $this->checkThresholdLimit($deviceId, $plug, $energy);
                $this->checkAnomalies($deviceId, $data);
                $this->checkConnectivity($deviceId, $data);
           }
        } catch (\Exception $e) {
            Log::error("Monitoring Service Error: " . $e->getMessage());
        }
    }

    /**
     * Check if today's accumulated kWh reaches the daily energy limit
     */
    protected function checkThresholdLimit(int $deviceId, array $plug, array $energy): void
    {
        $thresholdValue = (float) ($plug['threshold_value'] ?? $plug['daily_limit'] ?? 0);
        if ($thresholdValue <= 0) return;

        $thresholdType = (string) ($plug['threshold_type'] ?? 'daily');
        if (!in_array($thresholdType, ['daily', 'weekly', 'monthly'], true)) {
            $thresholdType = 'daily';
        }

        $usageKey = $thresholdType . '_kwh';
        $usage = (float) ($energy[$usageKey] ?? ($thresholdType === 'daily' ? ($plug['daily_kwh'] ?? 0) : 0));
        $deviceName = "Device {$deviceId}";

        if ($usage >= $thresholdValue) {
            $notifyKey = "critical_{$thresholdType}";
            if ($this->shouldNotify($deviceId, $notifyKey, 5)) {
                $periodLabel = ucfirst($thresholdType);
                $this->sendNotification($deviceId, [
                    'title' => "{$periodLabel} Energy Limit Reached",
                    'message' => "{$deviceName} used {$usage} / {$thresholdValue} kWh ({$thresholdType}) and was automatically turned off.",
                    'device_name' => $deviceName,
                    'severity' => 'critical',
                    'type' => 'alert',
                    'action' => 'View Device'
                ]);
                
                // Automatically turn off the device
                $this->firebase->setControl($deviceId, false);

                // Create Maintenance Alert
                MaintenanceAlert::create([
                    'device_id' => "PLUG{$deviceId}",
                    'plug_number' => $deviceId,
                    'message' => "{$deviceName} reached its {$thresholdType} energy limit ({$usage} / {$thresholdValue} kWh) and was turned off.",
                    'status' => 'critical',
                ]);

                event(new ThresholdExceeded($deviceId, $usage, $thresholdValue));
            }
        }
    }

    /**
     * Check for anomalies (based on anomaly flag from ESP32 or statistical analysis)
     */
    protected function checkAnomalies(int $deviceId, array $data): void
    {
        $voltage = $data['voltage'] ?? 220;
        $deviceName = "Plug {$deviceId}";
        $anomalyType = null;
        $message = "";

        if ($voltage < 200) {
            $anomalyType = 'under_voltage';
            $message = "{$deviceName} detected Under-Voltage anomaly ({$voltage}V). Device was turned off.";
        } elseif ($voltage > 260) {
            $anomalyType = 'over_voltage';
            $message = "{$deviceName} detected Over-Voltage anomaly ({$voltage}V). Device was turned off.";
        } elseif ($data['anomaly'] ?? false) {
            $anomalyType = 'general_anomaly';
            $message = "Unusual power consumption pattern detected on {$deviceName}. Device was turned off.";
        }

        if ($anomalyType) {
            if ($this->shouldNotify($deviceId, $anomalyType, 30)) {
                $this->sendNotification($deviceId, [
                    'title' => 'Anomaly Detected: ' . ucwords(str_replace('_', ' ', $anomalyType)),
                    'message' => $message,
                    'device_name' => $deviceName,
                    'severity' => 'critical',
                    'type' => 'alert',
                    'action' => 'Check Status'
                ]);

                // Automatically turn off the device
                $this->firebase->setControl($deviceId, false);

                // Create Maintenance Alert
                MaintenanceAlert::create([
                    'device_id' => "PLUG{$deviceId}",
                    'plug_number' => $deviceId,
                    'message' => $message,
                    'status' => 'critical',
                ]);
            }
        }
    }

    /**
     * Check for connectivity/offline status
     */
    protected function checkConnectivity(int $deviceId, array $data): void
    {
        // Connectivity logic can be complex; simplified for this implementation
    }

    /**
     * Send notification to Database and Firebase
     */
    protected function sendNotification(int $deviceId, array $payload): void
    {
        // 1. Send to Database for all users (or specific user)
        $users = User::all();
        foreach ($users as $user) {
            $user->notify(new DeviceAlertNotification($payload));
        }

        // 2. Push to Firebase for real-time frontend listener
        $this->firebase->pushNotification($deviceId, $payload);
        
        Log::info("Notification sent for Device {$deviceId}: " . $payload['title']);
    }

    /**
     * Rate limiting for notifications to avoid spam
     */
    protected function shouldNotify(int $deviceId, string $type, int $minutes): bool
    {
        $key = "notif_lock_{$deviceId}_{$type}";
        if (Cache::has($key)) {
            return false;
        }

        Cache::put($key, true, now()->addMinutes($minutes));
        return true;
    }
}
