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
use Illuminate\Support\Carbon;

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

                $energy = $this->syncEnergyUsage($deviceId, $data, $plug, $energy);
                $this->checkThresholdLimit($deviceId, $plug, $energy);
                $this->checkAnomalies($deviceId, $data);
                $this->checkConnectivity($deviceId, $data);
            }
        } catch (\Throwable $e) {
            Log::error("Monitoring Service Error: " . $e->getMessage());
        }
    }

    /**
     * Convert cumulative meter energy into period counters used by the app.
     */
    protected function syncEnergyUsage(int $deviceId, array $live, array $plug, array $energy): array
    {
        $rawTotal = $live['energy'] ?? null;
        if (!is_numeric($rawTotal)) {
            return $energy;
        }

        $currentTotalKwh = (float) $rawTotal;
        if ($currentTotalKwh < 0) {
            return $energy;
        }

        $prevRaw = $plug['meter_total_kwh'] ?? $energy['meter_total_kwh'] ?? null;
        $prevTotalKwh = is_numeric($prevRaw) ? (float) $prevRaw : null;

        $deltaKwh = 0.0;
        if ($prevTotalKwh !== null) {
            $deltaKwh = $currentTotalKwh - $prevTotalKwh;

            // Ignore impossible jumps and meter rollbacks.
            if ($deltaKwh < 0 || $deltaKwh > 5) {
                $deltaKwh = 0.0;
            }
        }

        $daily = (float) ($energy['daily_kwh'] ?? $plug['daily_kwh'] ?? 0);
        $weekly = (float) ($energy['weekly_kwh'] ?? $plug['weekly_kwh'] ?? 0);
        $monthly = (float) ($energy['monthly_kwh'] ?? $plug['monthly_kwh'] ?? 0);

        $now = Carbon::now();
        $currentDayKey = $now->toDateString();
        $currentWeekKey = $now->format('o-W');
        $currentMonthKey = $now->format('Y-m');

        $savedDayKey = (string) ($energy['daily_key'] ?? $plug['daily_key'] ?? '');
        $savedWeekKey = (string) ($energy['weekly_key'] ?? $plug['weekly_key'] ?? '');
        $savedMonthKey = (string) ($energy['monthly_key'] ?? $plug['monthly_key'] ?? '');

        if ($savedDayKey !== $currentDayKey) {
            $daily = 0.0;
        }

        if ($savedWeekKey !== $currentWeekKey) {
            $weekly = 0.0;
        }

        if ($savedMonthKey !== $currentMonthKey) {
            $monthly = 0.0;
        }

        if ($deltaKwh > 0) {
            $daily += $deltaKwh;
            $weekly += $deltaKwh;
            $monthly += $deltaKwh;
        }

        $currentPower = is_numeric($live['power'] ?? null)
            ? (float) $live['power']
            : (float) ($plug['current_power'] ?? 0);

        $timestamp = time();
        $updates = [
            "Energy/PLUG{$deviceId}/meter_total_kwh" => $currentTotalKwh,
            "Energy/PLUG{$deviceId}/daily_kwh" => $daily,
            "Energy/PLUG{$deviceId}/weekly_kwh" => $weekly,
            "Energy/PLUG{$deviceId}/monthly_kwh" => $monthly,
            "Energy/PLUG{$deviceId}/daily_key" => $currentDayKey,
            "Energy/PLUG{$deviceId}/weekly_key" => $currentWeekKey,
            "Energy/PLUG{$deviceId}/monthly_key" => $currentMonthKey,
            "plugs/plug{$deviceId}/meter_total_kwh" => $currentTotalKwh,
            "plugs/plug{$deviceId}/daily_kwh" => $daily,
            "plugs/plug{$deviceId}/weekly_kwh" => $weekly,
            "plugs/plug{$deviceId}/monthly_kwh" => $monthly,
            "plugs/plug{$deviceId}/daily_key" => $currentDayKey,
            "plugs/plug{$deviceId}/weekly_key" => $currentWeekKey,
            "plugs/plug{$deviceId}/monthly_key" => $currentMonthKey,
            "plugs/plug{$deviceId}/current_power" => $currentPower,
            "plugs/plug{$deviceId}/last_updated" => $timestamp,
        ];

        if (!$this->firebase->patchData($updates)) {
            return $energy;
        }

        return [
            'meter_total_kwh' => $currentTotalKwh,
            'daily_kwh' => $daily,
            'weekly_kwh' => $weekly,
            'monthly_kwh' => $monthly,
        ];
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
        $rawVoltage = $data['voltage'] ?? null;
        $voltage = is_numeric($rawVoltage) ? (float) $rawVoltage : null;
        $deviceName = "Plug {$deviceId}";
        $anomalyType = null;
        $message = "";

        // Ignore invalid/missing sensor values (e.g., 0V from failed reads).
        if ($voltage !== null && $voltage > 0 && $voltage < 200) {
            $anomalyType = 'under_voltage';
            $message = "{$deviceName} detected Under-Voltage anomaly ({$voltage}V). Device was turned off.";
        } elseif ($voltage !== null && $voltage > 260) {
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
        // Firebase is the primary notification store for the MongoDB setup.
        $this->firebase->pushNotification($deviceId, $payload);

        // Laravel's database notification channel may require a SQL PDO connection.
        try {
            $users = User::all();
            foreach ($users as $user) {
                $user->notify(new DeviceAlertNotification($payload));
            }
        } catch (\Throwable $e) {
            Log::warning('Database notification persistence skipped.', [
                'error' => $e->getMessage(),
            ]);
        }

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
