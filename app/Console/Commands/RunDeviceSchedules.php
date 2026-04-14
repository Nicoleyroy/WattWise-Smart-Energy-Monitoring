<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class RunDeviceSchedules extends Command
{
    private const LEGACY_SCHEDULE_ID = 'legacy_scheduled_time';

    protected $signature = 'schedule:run-device-schedules';
    protected $description = 'Check Firebase schedules and execute any that are due';

    public function handle(FirebaseService $firebase)
    {
        $now = now();
        $this->line("Checking schedules at " . $now->toDateTimeString());

        // Get all schedules from Firebase (Schedule/{PLUG}/items)
        $allSchedules = $firebase->getAllSchedules();

        if (empty($allSchedules)) {
            $this->line('No schedules found in Firebase.');
            return;
        }

        $executed = 0;

        foreach ($allSchedules as $plugKey => $plugData) {
            // Extract device ID from key e.g. "PLUG1" -> 1
            if (!preg_match('/PLUG(\d+)/', $plugKey, $matches)) {
                continue;
            }
            $deviceId = (int) $matches[1];

            $schedules = $this->getSchedulesForDevice($plugData);
            if (empty($schedules)) {
                continue;
            }

            foreach ($schedules as $scheduleId => $schedule) {
                if (empty($schedule['is_active'])) {
                    continue;
                }

                $startTime = $schedule['start_time'] ?? null;
                $endTime = $schedule['end_time'] ?? null;
                $status = $schedule['status'] ?? 'pending';
                if (!$startTime || !$endTime) {
                    continue;
                }

                $recurringDays = $this->normalizeDaysOfWeek($schedule['days_of_week'] ?? null);
                $isRecurring = !empty($recurringDays);

                $startDt = Carbon::parse($startTime);
                $endDt = Carbon::parse($endTime);
                if ($isRecurring) {
                    if (!in_array($now->dayOfWeek, $recurringDays, true)) {
                        if ($status === 'active') {
                            $schedule['status'] = 'pending';
                            $saved = $this->persistSchedule($firebase, $deviceId, $scheduleId, $schedule);
                            if ($saved) {
                                $schedules[$scheduleId] = $schedule;
                            }
                        }
                        continue;
                    }

                    $startDt = $now->copy()->startOfDay()->addHours((int) Carbon::parse($startTime)->hour)->addMinutes((int) Carbon::parse($startTime)->minute);
                    $endDt = $now->copy()->startOfDay()->addHours((int) Carbon::parse($endTime)->hour)->addMinutes((int) Carbon::parse($endTime)->minute);
                }

                $name = $schedule['name'] ?? 'Schedule';
                $updated = false;

                try {
                    if ($status === 'pending' && $now->gte($startDt) && $now->lt($endDt)) {
                        $success = $firebase->setControl($deviceId, true);
                        if ($success) {
                            $this->info("✓ Started '{$name}' → PLUG{$deviceId} turned ON");
                            $schedule['status'] = 'active';
                            $updated = true;
                            $executed++;
                            $firebase->appendScheduleHistory($deviceId, [
                                'event' => 'schedule_started',
                                'name' => $name,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'status' => 'active',
                                'schedule_id' => $scheduleId,
                            ]);
                        } else {
                            $this->error("✗ Failed to turn ON PLUG{$deviceId} for schedule '{$name}'");
                        }
                    } elseif ($status === 'active' && $now->gte($endDt)) {
                        $otherRunning = $this->hasOtherRunningSchedule($schedules, $scheduleId, $now);
                        $success = $otherRunning ? true : $firebase->setControl($deviceId, false);
                        if ($success) {
                            $this->info("✓ Ended '{$name}' → PLUG{$deviceId}" . ($otherRunning ? ' kept ON (another schedule running)' : ' turned OFF'));
                            $schedule['status'] = $isRecurring ? 'pending' : 'completed';
                            if (!$isRecurring) {
                                $schedule['is_active'] = false;
                            }
                            $updated = true;
                            $executed++;
                            $firebase->appendScheduleHistory($deviceId, [
                                'event' => 'schedule_completed',
                                'name' => $name,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'status' => 'completed',
                                'schedule_id' => $scheduleId,
                            ]);
                        } else {
                            $this->error("✗ Failed to turn OFF PLUG{$deviceId} for schedule '{$name}'");
                        }
                    } elseif (!$isRecurring && $status === 'pending' && $now->gte($endDt)) {
                        $schedule['status'] = 'completed';
                        $schedule['is_active'] = false;
                        $updated = true;
                        $firebase->appendScheduleHistory($deviceId, [
                            'event' => 'schedule_auto_completed',
                            'name' => $name,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'status' => 'completed',
                            'schedule_id' => $scheduleId,
                        ]);
                    }

                    if ($updated) {
                        $saved = $this->persistSchedule($firebase, $deviceId, $scheduleId, $schedule);
                        if (!$saved) {
                            $this->error("✗ Failed to persist updated schedule state for PLUG{$deviceId}");
                            Log::warning('Failed to persist schedule update', [
                                'plug' => $plugKey,
                                'schedule_id' => $scheduleId,
                                'schedule' => $schedule,
                            ]);
                        }

                        $schedules[$scheduleId] = $schedule;
                    }
                } catch (\Exception $e) {
                    $this->error("Error executing '{$name}': " . $e->getMessage());
                    Log::error('Device schedule error', [
                        'plug'  => $plugKey,
                        'schedule_id' => $scheduleId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("Done. Executed {$executed} schedule(s).");
    }

    private function getSchedulesForDevice(array $plugData): array
    {
        $items = $plugData['items'] ?? null;
        if (is_array($items) && !empty($items)) {
            $normalized = [];
            foreach ($items as $id => $schedule) {
                if (!is_array($schedule)) {
                    continue;
                }
                $normalized[(string) $id] = $schedule;
            }
            return $normalized;
        }

        $legacy = $plugData['scheduled_time'] ?? null;
        if (is_array($legacy) && !empty($legacy)) {
            return [self::LEGACY_SCHEDULE_ID => $legacy];
        }

        return [];
    }

    private function persistSchedule(FirebaseService $firebase, int $deviceId, string $scheduleId, array $schedule): bool
    {
        if ($scheduleId === self::LEGACY_SCHEDULE_ID) {
            return $firebase->updateSchedule($deviceId, $schedule);
        }

        return $firebase->updateScheduleItem($deviceId, $scheduleId, $schedule);
    }

    private function hasOtherRunningSchedule(array $schedules, string $currentId, Carbon $now): bool
    {
        foreach ($schedules as $scheduleId => $schedule) {
            if ($scheduleId === $currentId) {
                continue;
            }

            if (!($schedule['is_active'] ?? false)) {
                continue;
            }

            $startTime = $schedule['start_time'] ?? null;
            $endTime = $schedule['end_time'] ?? null;
            if (!$startTime || !$endTime) {
                continue;
            }

            $recurringDays = $this->normalizeDaysOfWeek($schedule['days_of_week'] ?? null);
            $isRecurring = !empty($recurringDays);

            if ($isRecurring && !in_array($now->dayOfWeek, $recurringDays, true)) {
                continue;
            }

            $startDt = Carbon::parse($startTime);
            $endDt = Carbon::parse($endTime);
            if ($isRecurring) {
                $startDt = $now->copy()->startOfDay()->addHours((int) $startDt->hour)->addMinutes((int) $startDt->minute);
                $endDt = $now->copy()->startOfDay()->addHours((int) $endDt->hour)->addMinutes((int) $endDt->minute);
            }

            if ($now->gte($startDt) && $now->lt($endDt)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeDaysOfWeek($days): array
    {
        if (!is_array($days)) {
            return [];
        }

        $normalized = [];
        foreach ($days as $day) {
            $intDay = (int) $day;
            if ($intDay >= 0 && $intDay <= 6) {
                $normalized[] = $intDay;
            }
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);
        return $normalized;
    }
}
