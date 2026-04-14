<?php

namespace App\Services;

use App\Models\ScheduleHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class FirebaseService
{
    /**
     * Firebase Realtime Database URL
     */
    protected string $databaseUrl;

    /**
     * Firebase API Key
     */
    protected ?string $apiKey;

    /**
     * Request timeout in seconds
     */
    protected int $timeout = 10;

    /**
     * Whether SSL certificate verification is enabled for Firebase HTTP calls.
     */
    protected bool $verifySsl;

    public function __construct()
    {
        $this->databaseUrl = rtrim(config('iot.firebase.database_url', env('FIREBASE_DATABASE_URL', '')), '/');
        $this->apiKey = config('iot.firebase.api_key', env('FIREBASE_API_KEY'));

        $verifySsl = config('iot.firebase.verify_ssl');
        $this->verifySsl = is_null($verifySsl) ? !app()->environment('local') : (bool) $verifySsl;
    }

    protected function client()
    {
        $client = Http::timeout($this->timeout);

        return $this->verifySsl ? $client : $client->withoutVerifying();
    }

    /**
     * Get live data for a specific plug
     */
    public function getLiveData(int $deviceId): ?array
    {
        try {
            $path = "Live/PLUG{$deviceId}.json";
            $response = $this->client()->get($this->buildUrl($path));
            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error("Error getting live data: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all live data
     */
    public function getAllLiveData(): array
    {
        try {
            $path = "Live.json";
            $response = $this->client()->get($this->buildUrl($path));
            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Exception $e) {
            Log::error("Error getting all live data: " . $e->getMessage());
            return [];
        }
    }

   
    public function setDailyLimit(int $deviceId, float $dailyLimit): bool
    {
        return $this->setThresholdConfig($deviceId, $dailyLimit, 'daily');
    }

    public function setThreshold(int $deviceId, float $threshold): bool
    {
        return $this->setThresholdConfig($deviceId, $threshold, 'daily');
    }

    public function setThresholdConfig(int $deviceId, float $value, string $type = 'daily'): bool
    {
        $type = in_array($type, ['daily', 'weekly', 'monthly'], true) ? $type : 'daily';

        try {
            $response = $this->client()->patch($this->buildUrl('.json'), [
                "plugs/plug{$deviceId}/threshold_value" => $value,
                "plugs/plug{$deviceId}/threshold_type" => $type,
                // Keep this for backward compatibility with existing UI/queries.
                "plugs/plug{$deviceId}/daily_limit" => $value,
                "threshold/PLUG{$deviceId}" => $value,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error setting threshold config: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get daily kWh limit for a single device
     */
    public function getDailyLimit(int $deviceId): ?float
    {
        try {
            $path = "plugs/plug{$deviceId}/daily_limit.json";
            $response = $this->client()->get($this->buildUrl($path));
            if ($response->successful()) {
                $value = $response->json();
                if ($value !== null) {
                    return (float) $value;
                }
            }

            // Backward-compatible fallback for legacy threshold node.
            $legacyPath = "threshold/PLUG{$deviceId}.json";
            $legacyResponse = $this->client()->get($this->buildUrl($legacyPath));
            if ($legacyResponse->successful()) {
                $legacyValue = $legacyResponse->json();
                return $legacyValue === null ? null : (float) $legacyValue;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error getting daily limit for plug{$deviceId}: " . $e->getMessage());
            return null;
        }
    }

    public function getThreshold(int $deviceId): ?float
    {
        return $this->getDailyLimit($deviceId);
    }

    public function getThresholdConfig(int $deviceId): array
    {
        try {
            $path = "plugs/plug{$deviceId}.json";
            $response = $this->client()->get($this->buildUrl($path));
            if ($response->successful()) {
                $plug = $response->json() ?? [];
                $value = $plug['threshold_value'] ?? $plug['daily_limit'] ?? null;
                $type = $plug['threshold_type'] ?? 'daily';

                return [
                    'threshold_value' => $value === null ? 0.0 : (float) $value,
                    'threshold_type' => in_array($type, ['daily', 'weekly', 'monthly'], true) ? $type : 'daily',
                ];
            }
        } catch (\Exception $e) {
            Log::error("Error getting threshold config for plug{$deviceId}: " . $e->getMessage());
        }

        return [
            'threshold_value' => 0.0,
            'threshold_type' => 'daily',
        ];
    }

    /**
     * Set control state (relay ON/OFF)
     */
    public function setControl(int $deviceId, bool $state): bool
    {
        try {
            $response = $this->client()->patch($this->buildUrl('.json'), [
                "Control/PLUG{$deviceId}" => $state,
                "Status/PLUG{$deviceId}" => $state ? 'ON' : 'OFF',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error setting control: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set friendly name for a plug.
     */
    public function setDeviceName(int $deviceId, string $name): bool
    {
        try {
            $response = $this->client()->patch($this->buildUrl('.json'), [
                "plugs/plug{$deviceId}/name" => $name,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error setting device name: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get one device registration node from Firebase Realtime Database.
     */
    public function getDeviceRegistrationData(string $deviceId): mixed
    {
        try {
            $path = 'devices/' . rawurlencode(trim($deviceId)) . '.json';
            $response = $this->client()->get($this->buildUrl($path));

            if (!$response->successful()) {
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error getting device registration data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all control states
     */
    public function getAllControls(): array
    {
        try {
            $path = "Control.json";
            $response = $this->client()->get($this->buildUrl($path));
            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Exception $e) {
            Log::error("Error getting all controls: " . $e->getMessage());
            return [];
        }
    }

   /**
     * Get all plug data from the clean kWh-based structure
     */
    public function getAllPlugData(): array
    {
        try {
            $path = "plugs.json";
            $response = $this->client()->get($this->buildUrl($path));
            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Exception $e) {
            Log::error("Error getting plug data: " . $e->getMessage());
            return [];
        }
    }

    public function getAllEnergyData(): array
    {
        try {
            $path = "Energy.json";
            $response = $this->client()->get($this->buildUrl($path));
            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Exception $e) {
            Log::error("Error getting energy data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Reset period counters used by threshold enforcement.
     *
     * @return int Number of devices updated.
     */
    public function resetEnergyCounters(string $period): int
    {
        $period = in_array($period, ['daily', 'weekly', 'monthly'], true) ? $period : 'daily';
        $counterKey = $period . '_kwh';

        try {
            $energyData = $this->getAllEnergyData();
            $plugData = $this->getAllPlugData();
            $updates = [];
            $updatedDevices = [];

            foreach ($energyData as $deviceKey => $data) {
                if (!is_array($data)) {
                    continue;
                }

                $updates["Energy/{$deviceKey}/{$counterKey}"] = 0;
                if (preg_match('/PLUG(\d+)/', $deviceKey, $matches)) {
                    $updatedDevices[(int) $matches[1]] = true;
                }
            }

            foreach ($plugData as $plugKey => $data) {
                if (!preg_match('/plug(\d+)/', $plugKey, $matches)) {
                    continue;
                }

                $deviceId = (int) $matches[1];
                $updates["plugs/plug{$deviceId}/{$counterKey}"] = 0;
                $updatedDevices[$deviceId] = true;
            }

            if (empty($updates)) {
                return 0;
            }

            $response = $this->client()->patch($this->buildUrl('.json'), $updates);
            if (!$response->successful()) {
                Log::error("Failed to reset {$period} energy counters.", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return 0;
            }

            return count($updatedDevices);
        } catch (\Exception $e) {
            Log::error("Error resetting {$period} energy counters: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all daily kWh limits
     */
    public function getAllThresholds(): array
    {
        try {
            $plugData = $this->getAllPlugData();
            $limits = [];

            foreach ($plugData as $plugKey => $data) {
                if (preg_match('/plug(\d+)/', $plugKey, $matches)) {
                    $limits["PLUG{$matches[1]}"] = (float) ($data['threshold_value'] ?? $data['daily_limit'] ?? 0);
                }
            }

            return $limits;
        } catch (\Exception $e) {
            Log::error("Error getting all daily limits: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get history data
     */
    public function getDeviceHistory(int $deviceId, int $limit = 50): array
    {
        try {
            $path = "History.json";
            $baseUrl = $this->buildUrl($path);
            $separator = str_contains($baseUrl, '?') ? '&' : '?';
            $url = $baseUrl . $separator . "orderBy=\"\$key\"&limitToLast={$limit}";
            $response = $this->client()->get($url);
            
            if ($response->successful()) {
                $allHistory = $response->json() ?? [];
                $deviceHistory = [];
                foreach ($allHistory as $ts => $entry) {
                    if (isset($entry["PLUG{$deviceId}"])) {
                        $deviceHistory[$ts] = $entry["PLUG{$deviceId}"];
                    }
                }
                return $deviceHistory;
            }
            return [];
        } catch (\Exception $e) {
            Log::error("Error getting history: " . $e->getMessage());
            return [];
        }
    }

    // ─── Schedule Methods ───

    /**
     * Get the single schedule for a device from Firebase
     */
    public function getSchedule(int $deviceId): ?array
    {
        try {
            $path = "Schedule/PLUG{$deviceId}/scheduled_time.json";
            $response = $this->client()->get($this->buildUrl($path));
            if ($response->successful() && $response->json()) {
                return $response->json();
            }
            return null;
        } catch (\Exception $e) {
            Log::error("Error getting schedule: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all schedule items for a device from Firebase.
     */
    public function getScheduleItems(int $deviceId): array
    {
        try {
            $path = "Schedule/PLUG{$deviceId}/items.json";
            $response = $this->client()->get($this->buildUrl($path));
            $items = $response->successful() ? ($response->json() ?? []) : [];
            if (!is_array($items)) {
                return [];
            }

            $normalized = [];
            foreach ($items as $id => $item) {
                if (!is_array($item)) {
                    continue;
                }
                $item['id'] = (string) $id;
                $normalized[] = $item;
            }

            usort($normalized, function ($a, $b) {
                $aTs = strtotime((string) ($a['start_time'] ?? '')) ?: 0;
                $bTs = strtotime((string) ($b['start_time'] ?? '')) ?: 0;
                return $aTs <=> $bTs;
            });

            return $normalized;
        } catch (\Exception $e) {
            Log::error("Error getting schedule items: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get one schedule item by id.
     */
    public function getScheduleItem(int $deviceId, string $scheduleId): ?array
    {
        try {
            $path = "Schedule/PLUG{$deviceId}/items/{$scheduleId}.json";
            $response = $this->client()->get($this->buildUrl($path));
            if ($response->successful() && is_array($response->json())) {
                $item = $response->json();
                $item['id'] = $scheduleId;
                return $item;
            }
            return null;
        } catch (\Exception $e) {
            Log::error("Error getting schedule item: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create one schedule item and return its id.
     */
    public function createScheduleItem(int $deviceId, array $data): ?string
    {
        try {
            $path = "Schedule/PLUG{$deviceId}/items.json";
            $response = $this->client()->post($this->buildUrl($path), $data);
            if (!$response->successful()) {
                return null;
            }

            $payload = $response->json();
            return is_array($payload) ? ($payload['name'] ?? null) : null;
        } catch (\Exception $e) {
            Log::error("Error creating schedule item: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update one schedule item.
     */
    public function updateScheduleItem(int $deviceId, string $scheduleId, array $data): bool
    {
        try {
            $path = "Schedule/PLUG{$deviceId}/items/{$scheduleId}.json";
            $response = $this->client()->put($this->buildUrl($path), $data);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error updating schedule item: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete one schedule item.
     */
    public function deleteScheduleItem(int $deviceId, string $scheduleId): bool
    {
        try {
            $path = "Schedule/PLUG{$deviceId}/items/{$scheduleId}.json";
            $response = $this->client()->delete($this->buildUrl($path));
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error deleting schedule item: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create or update the single schedule in Firebase
     */
    public function updateSchedule(int $deviceId, array $data): bool
    {
        try {
            $path = "Schedule/PLUG{$deviceId}/scheduled_time.json";
            $response = $this->client()->put($this->buildUrl($path), $data);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error updating schedule: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete the schedule from Firebase
     */
    public function deleteSchedule(int $deviceId): bool
    {
        try {
            $path = "Schedule/PLUG{$deviceId}/scheduled_time.json";
            $response = $this->client()->delete($this->buildUrl($path));
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error deleting schedule: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Append an event entry to schedule history.
     */
    public function appendScheduleHistory(int $deviceId, array $event): bool
    {
        try {
            ScheduleHistory::create([
                'device_id' => $deviceId,
                'schedule_id' => isset($event['schedule_id']) ? (string) $event['schedule_id'] : null,
                'event' => (string) ($event['event'] ?? 'schedule_updated'),
                'name' => $event['name'] ?? null,
                'start_time' => isset($event['start_time']) && $event['start_time'] ? Carbon::parse($event['start_time']) : null,
                'end_time' => isset($event['end_time']) && $event['end_time'] ? Carbon::parse($event['end_time']) : null,
                'status' => $event['status'] ?? null,
                'logged_at' => isset($event['timestamp']) && $event['timestamp']
                    ? Carbon::parse($event['timestamp'])
                    : now(),
                'archived' => (bool) ($event['archived'] ?? false),
                'archived_at' => !empty($event['archived']) ? now() : null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Error appending schedule history: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get schedule history for one device (latest first).
     */
    public function getScheduleHistory(int $deviceId, ?int $limit = null): array
    {
        try {
            $query = ScheduleHistory::query()
                ->where('device_id', $deviceId)
                ->orderByDesc('logged_at');

            if (!is_null($limit)) {
                $query->limit(max(1, $limit));
            }

            return $query
                ->get()
                ->map(function (ScheduleHistory $item) {
                    return [
                        'id' => (string) $item->id,
                        'device_id' => (int) $item->device_id,
                        'schedule_id' => $item->schedule_id,
                        'event' => $item->event,
                        'name' => $item->name,
                        'start_time' => optional($item->start_time)->toIso8601String(),
                        'end_time' => optional($item->end_time)->toIso8601String(),
                        'status' => $item->status,
                        'timestamp' => optional($item->logged_at)->toIso8601String(),
                        'archived' => (bool) $item->archived,
                        'archived_at' => optional($item->archived_at)->toIso8601String(),
                    ];
                })
                ->values()
                ->all();
        } catch (\Exception $e) {
            Log::error("Error getting schedule history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Archive or unarchive a single schedule history entry.
     */
    public function setScheduleHistoryArchived(int $deviceId, string $historyId, bool $archived): bool
    {
        try {
            $item = ScheduleHistory::query()
                ->where('id', (int) $historyId)
                ->where('device_id', $deviceId)
                ->first();

            if (!$item) {
                return false;
            }

            $item->archived = $archived;
            $item->archived_at = $archived ? now() : null;
            return $item->save();
        } catch (\Exception $e) {
            Log::error("Error updating schedule history archive state: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all schedules across all devices (for the scheduler command)
     */
    public function getAllSchedules(): array
    {
        try {
            $path = "Schedule.json";
            $response = $this->client()->get($this->buildUrl($path));
            if ($response->successful() && $response->json()) {
                return $response->json();
            }
            return [];
        } catch (\Exception $e) {
            Log::error("Error getting all schedules: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Push a notification to Firebase Alerts for real-time frontend listener
     */
    public function pushNotification(int $deviceId, array $payload): bool
    {
        try {
            $path = "Alerts/PLUG{$deviceId}.json";
            $payload['timestamp'] = time();
            $response = $this->client()->post($this->buildUrl($path), $payload);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error pushing notification to Firebase: " . $e->getMessage());
            return false;
        }
    }

    protected function buildUrl(string $path): string
    {
        $url = $this->databaseUrl . '/' . ltrim($path, '/');
        if ($this->apiKey) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'auth=' . $this->apiKey;
        }
        return $url;
    }
}
