<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IoTService
{
    /**
     * IoT device base URL - configure this in your .env file
     * Example: IOT_BASE_URL=http://192.168.1.100:8080
     */
    protected string $baseUrl;

    /**
     * Request timeout in seconds
     */
    protected int $timeout = 5;

    public function __construct()
    {
        $this->baseUrl = config('iot.base_url', env('IOT_BASE_URL', 'http://localhost:8080'));
    }

    /**
     * Get summary data for dashboard cards
     */
    public function getSummaryData(): array
    {
        $currentPower = $this->getCurrentPower();
        $todayUsage = $this->getTodayUsage();
        $monthlyRecord = $this->getMonthlyRecords();
        $thresholds = $this->getThresholds();

        return [
            'current_power' => [
                'value' => $currentPower['watts'] ?? 0,
                'unit' => 'W',
            ],
            'record' => [
                'value' => $monthlyRecord['total_kwh'] ?? 0,
                'unit' => 'kWh',
            ],
            'today_usage' => [
                'value' => $todayUsage['kwh'] ?? 0,
                'unit' => 'kWh',
            ],
            'thresholds' => [
                'count' => count($thresholds),
            ],
        ];
    }

    /**
     * Get all ports/devices
     */
    public function getAllPorts(): array
    {
        try {
            // Replace with your actual IoT endpoint
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/api/ports");

            if ($response->successful()) {
                return $this->formatPorts($response->json());
            }

            // Fallback to mock data if IoT is not available
            Log::warning('IoT device not available, using mock data');
            return $this->getMockPorts();
        } catch (\Exception $e) {
            Log::error('Failed to fetch ports from IoT: ' . $e->getMessage());
            return $this->getMockPorts();
        }
    }

    /**
     * Toggle port ON/OFF
     */
    public function togglePort(int $portId, bool $state): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/ports/{$portId}/toggle", [
                    'state' => $state ? 'on' : 'off',
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to toggle port');
        } catch (\Exception $e) {
            Log::error("Failed to toggle port {$portId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get specific port status
     */
    public function getPortStatus(int $portId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/api/ports/{$portId}");

            if ($response->successful()) {
                return $this->formatPort($response->json());
            }

            throw new \Exception('Failed to fetch port status');
        } catch (\Exception $e) {
            Log::error("Failed to fetch port {$portId} status: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get current total power consumption
     */
    public function getCurrentPower(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/api/metrics/power");

            if ($response->successful()) {
                return $response->json();
            }

            // Fallback
            return ['watts' => 3650];
        } catch (\Exception $e) {
            Log::error('Failed to fetch current power: ' . $e->getMessage());
            return ['watts' => 3650];
        }
    }

    /**
     * Get today's energy usage
     */
    public function getTodayUsage(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/api/metrics/today");

            if ($response->successful()) {
                return $response->json();
            }

            // Fallback
            return ['kwh' => 23.0];
        } catch (\Exception $e) {
            Log::error('Failed to fetch today usage: ' . $e->getMessage());
            return ['kwh' => 23.0];
        }
    }

    /**
     * Get maintenance alerts
     */
    public function getAlerts(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/api/alerts");

            if ($response->successful()) {
                return $response->json();
            }

            // Fallback
            return [
                'heading' => 'Maintenance Alerts',
                'subheading' => '1 appliance needs attention',
                'alerts' => [
                    [
                        'title' => 'Refrigerator',
                        'message' => 'Refrigerator may need maintenance soon. Schedule a check.',
                        'badge_text' => 'Warning',
                    ],
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch alerts: ' . $e->getMessage());
            return [
                'heading' => 'Maintenance Alerts',
                'subheading' => 'No alerts',
                'alerts' => [],
            ];
        }
    }

    /**
     * Get monthly energy records
     */
    public function getMonthlyRecords(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/api/records/monthly");

            if ($response->successful()) {
                return $response->json();
            }

            // Fallback
            return ['total_kwh' => 112.5];
        } catch (\Exception $e) {
            Log::error('Failed to fetch monthly records: ' . $e->getMessage());
            return ['total_kwh' => 112.5];
        }
    }

    /**
     * Get active thresholds
     */
    public function getThresholds(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/api/thresholds");

            if ($response->successful()) {
                return $response->json();
            }

            // Fallback
            return [];
        } catch (\Exception $e) {
            Log::error('Failed to fetch thresholds: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format ports data from IoT response
     */
    protected function formatPorts(array $data): array
    {
        // Adjust this based on your IoT device response format
        return array_map([$this, 'formatPort'], $data);
    }

    /**
     * Format single port data
     */
    protected function formatPort(array $data): array
    {
        // Adjust this based on your IoT device response format
        return [
            'id' => $data['id'] ?? $data['port_id'] ?? 0,
            'name' => $data['name'] ?? $data['label'] ?? 'Port ' . ($data['id'] ?? 0),
            'is_on' => $data['is_on'] ?? $data['state'] === 'on' ?? false,
            'power' => $data['power_watts'] ?? $data['power'] ?? 0,
            'cost_per_hour' => $data['cost_per_hour'] ?? $data['cost'] ?? 0,
            'today_kwh' => $data['today_kwh'] ?? $data['kwh_today'] ?? 0,
            'status' => $data['status'] ?? 'Healthy',
            'status_tone' => $this->mapStatusTone($data['status'] ?? 'healthy'),
        ];
    }

    /**
     * Map status to tone
     */
    protected function mapStatusTone(string $status): string
    {
        return match (strtolower($status)) {
            'healthy', 'ok', 'normal' => 'healthy',
            'warning', 'check soon', 'maintenance' => 'warning',
            default => 'healthy',
        };
    }

    /**
     * Mock ports data (fallback when IoT is unavailable)
     */
    protected function getMockPorts(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Port 1',
                'is_on' => true,
                'power' => 1500,
                'cost_per_hour' => 18.75,
                'today_kwh' => 12.5,
                'status' => 'Healthy',
                'status_tone' => 'healthy',
            ],
            [
                'id' => 2,
                'name' => 'Port 2',
                'is_on' => true,
                'power' => 150,
                'cost_per_hour' => 3.60,
                'today_kwh' => 3.6,
                'status' => 'Check Soon',
                'status_tone' => 'warning',
            ],
            [
                'id' => 3,
                'name' => 'Port 3',
                'is_on' => false,
                'power' => 80,
                'cost_per_hour' => 0.96,
                'today_kwh' => 1.2,
                'status' => 'Healthy',
                'status_tone' => 'healthy',
            ],
        ];
    }
}

