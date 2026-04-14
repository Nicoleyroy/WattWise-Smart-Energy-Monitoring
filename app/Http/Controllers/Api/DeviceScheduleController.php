<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeviceScheduleController extends Controller
{
    private const LEGACY_SCHEDULE_ID = 'legacy_scheduled_time';
    private const VALID_DAYS_OF_WEEK = [0, 1, 2, 3, 4, 5, 6];
    private const MAX_SCHEDULES_PER_DEVICE = 4;

    protected FirebaseService $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    /**
     * Get the single schedule for a device from Firebase
     */
    public function show($deviceId)
    {
        $schedule = $this->firebase->getSchedule((int) $deviceId);
        return response()->json($schedule ?: []);
    }

    /**
     * List schedules for a device (multi-schedule).
     */
    public function index($deviceId)
    {
        return response()->json($this->getDeviceSchedules((int) $deviceId));
    }

    /**
     * Get recent schedule history for a device.
     */
    public function history($deviceId)
    {
        $history = $this->firebase->getScheduleHistory((int) $deviceId);
        return response()->json($history);
    }

    /**
     * Archive or unarchive one schedule history item.
     */
    public function archiveHistory(Request $request, $deviceId, $historyId)
    {
        $validated = $request->validate([
            'archived' => 'required|boolean',
        ]);

        $success = $this->firebase->setScheduleHistoryArchived(
            (int) $deviceId,
            (string) $historyId,
            (bool) $validated['archived'],
        );

        if ($success) {
            return response()->json(['success' => true]);
        }

        return response()->json(['message' => 'Failed to update archive state'], 500);
    }

    /**
     * Create or update the single schedule in Firebase
     */
    public function update(Request $request, $deviceId)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time'   => 'required|date|after:start_time',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|in:0,1,2,3,4,5,6',
        ]);

        $startAt = Carbon::parse($validated['start_time'])->utc();
        $endAt = Carbon::parse($validated['end_time'])->utc();
        $daysOfWeek = $this->normalizeDaysOfWeek($validated['days_of_week'] ?? null);

        if (!$this->hasNoOverlap((int) $deviceId, $startAt, $endAt, self::LEGACY_SCHEDULE_ID, $daysOfWeek)) {
            return response()->json([
                'message' => 'This schedule overlaps with another active schedule for this device.',
            ], 422);
        }

        $existing = $this->firebase->getSchedule((int) $deviceId);
        $status = 'pending';

        if ($existing) {
            // If the time has changed, reset the status back to pending
            if (($existing['start_time'] ?? '') !== $startAt->toIso8601String() ||
                ($existing['end_time'] ?? '') !== $endAt->toIso8601String()) {
                $status = 'pending';
            } else {
                $status = $existing['status'] ?? 'pending';
            }

            if ($status === 'completed' && now()->lt($endAt)) {
                $status = 'pending';
            }
        }

        $data = [
            'name'       => $validated['name'],
            'start_time' => $startAt->toIso8601String(),
            'end_time'   => $endAt->toIso8601String(),
            'days_of_week' => $daysOfWeek,
            'status'     => $status,
            'is_active'  => true,
            'device_id'  => (int) $deviceId,
        ];

        $success = $this->firebase->updateSchedule((int) $deviceId, $data);

        if ($success) {
            $this->firebase->appendScheduleHistory((int) $deviceId, [
                'event' => $existing ? 'schedule_updated' : 'schedule_created',
                'name' => $data['name'] ?? 'Schedule',
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'status' => $data['status'] ?? 'pending',
            ]);
            return response()->json($data);
        }

        return response()->json(['message' => 'Failed to save schedule in Firebase'], 500);
    }

    /**
     * Create a new schedule item for a device.
     */
    public function storeItem(Request $request, $deviceId)
    {
        if (count($this->getDeviceSchedules((int) $deviceId)) >= self::MAX_SCHEDULES_PER_DEVICE) {
            return response()->json([
                'message' => 'Maximum of 4 schedules per device is allowed.',
            ], 422);
        }

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time'   => 'required|date|after:start_time',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|in:0,1,2,3,4,5,6',
        ]);

        $startAt = Carbon::parse($validated['start_time'])->utc();
        $endAt = Carbon::parse($validated['end_time'])->utc();
        $daysOfWeek = $this->normalizeDaysOfWeek($validated['days_of_week'] ?? null);

        if (!$this->hasNoOverlap((int) $deviceId, $startAt, $endAt, null, $daysOfWeek)) {
            return response()->json([
                'message' => 'This schedule overlaps with another active schedule for this device.',
            ], 422);
        }

        $data = [
            'name' => $validated['name'],
            'start_time' => $startAt->toIso8601String(),
            'end_time' => $endAt->toIso8601String(),
            'days_of_week' => $daysOfWeek,
            'status' => 'pending',
            'is_active' => true,
            'device_id' => (int) $deviceId,
        ];

        $id = $this->firebase->createScheduleItem((int) $deviceId, $data);
        if (!$id) {
            return response()->json(['message' => 'Failed to create schedule'], 500);
        }

        $data['id'] = $id;
        $this->firebase->appendScheduleHistory((int) $deviceId, [
            'event' => 'schedule_created',
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => $data['status'],
            'schedule_id' => $id,
        ]);

        return response()->json($data, 201);
    }

    /**
     * Update one schedule item.
     */
    public function updateItem(Request $request, $deviceId, $scheduleId)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time'   => 'required|date|after:start_time',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|in:0,1,2,3,4,5,6',
        ]);

        $existing = $this->resolveSchedule((int) $deviceId, (string) $scheduleId);
        if (!$existing) {
            return response()->json(['message' => 'Schedule not found'], 404);
        }

        $startAt = Carbon::parse($validated['start_time'])->utc();
        $endAt = Carbon::parse($validated['end_time'])->utc();
        $daysOfWeek = $this->normalizeDaysOfWeek($validated['days_of_week'] ?? null);

        if (!$this->hasNoOverlap((int) $deviceId, $startAt, $endAt, (string) $scheduleId, $daysOfWeek)) {
            return response()->json([
                'message' => 'This schedule overlaps with another active schedule for this device.',
            ], 422);
        }

        $status = $existing['status'] ?? 'pending';
        if (($existing['start_time'] ?? '') !== $startAt->toIso8601String() ||
            ($existing['end_time'] ?? '') !== $endAt->toIso8601String()) {
            $status = 'pending';
        }

        $data = [
            'name' => $validated['name'],
            'start_time' => $startAt->toIso8601String(),
            'end_time' => $endAt->toIso8601String(),
            'days_of_week' => $daysOfWeek,
            'status' => $status,
            'is_active' => true,
            'device_id' => (int) $deviceId,
        ];

        $saved = $this->persistSchedule((int) $deviceId, (string) $scheduleId, $existing, $data);
        if (!$saved) {
            return response()->json(['message' => 'Failed to update schedule'], 500);
        }

        $data['id'] = (string) $scheduleId;
        $this->firebase->appendScheduleHistory((int) $deviceId, [
            'event' => 'schedule_updated',
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => $data['status'],
            'schedule_id' => (string) $scheduleId,
        ]);

        return response()->json($data);
    }

    /**
     * Delete the schedule from Firebase
     */
    public function destroy($deviceId)
    {
        $existing = $this->firebase->getSchedule((int) $deviceId);
        $success = $this->firebase->deleteSchedule((int) $deviceId);

        if ($success) {
            $this->firebase->appendScheduleHistory((int) $deviceId, [
                'event' => 'schedule_deleted',
                'name' => $existing['name'] ?? 'Schedule',
                'start_time' => $existing['start_time'] ?? null,
                'end_time' => $existing['end_time'] ?? null,
                'status' => $existing['status'] ?? null,
            ]);
            return response()->json(['message' => 'Schedule deleted successfully']);
        }

        return response()->json(['message' => 'Failed to delete schedule from Firebase'], 500);
    }

    /**
     * Delete one schedule item.
     */
    public function destroyItem($deviceId, $scheduleId)
    {
        $existing = $this->resolveSchedule((int) $deviceId, (string) $scheduleId);
        if (!$existing) {
            return response()->json(['message' => 'Schedule not found'], 404);
        }

        $success = ((string) $scheduleId === self::LEGACY_SCHEDULE_ID)
            ? $this->firebase->deleteSchedule((int) $deviceId)
            : $this->firebase->deleteScheduleItem((int) $deviceId, (string) $scheduleId);

        if (!$success) {
            return response()->json(['message' => 'Failed to delete schedule'], 500);
        }

        $this->firebase->appendScheduleHistory((int) $deviceId, [
            'event' => 'schedule_deleted',
            'name' => $existing['name'] ?? 'Schedule',
            'start_time' => $existing['start_time'] ?? null,
            'end_time' => $existing['end_time'] ?? null,
            'status' => $existing['status'] ?? null,
            'schedule_id' => (string) $scheduleId,
        ]);

        return response()->json(['message' => 'Schedule deleted successfully']);
    }

    /**
     * Toggle active state of the schedule
     */
    public function activate(Request $request, $deviceId)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $existing = $this->firebase->getSchedule((int) $deviceId);

        if (!$existing) {
            return response()->json(['message' => 'Schedule not found'], 404);
        }

        $existing['is_active'] = $validated['is_active'];

        $startAt = isset($existing['start_time']) ? Carbon::parse($existing['start_time']) : null;
        $endAt = isset($existing['end_time']) ? Carbon::parse($existing['end_time']) : null;
        $now = now();

        if ($validated['is_active'] === true) {
            if ($endAt && $now->gte($endAt)) {
                $existing['status'] = 'completed';
                $existing['is_active'] = false;
            } elseif ($startAt && $endAt && $now->gte($startAt) && $now->lt($endAt)) {
                $existing['status'] = 'active';
                $this->firebase->setControl((int) $deviceId, true);
            } else {
                $existing['status'] = 'pending';
            }
        } else {
            if (($existing['status'] ?? 'pending') === 'active') {
                $this->firebase->setControl((int) $deviceId, false);
            }
            $existing['status'] = ($endAt && $now->gte($endAt)) ? 'completed' : 'pending';
        }

        $success = $this->firebase->updateSchedule((int) $deviceId, $existing);

        if ($success) {
            $this->firebase->appendScheduleHistory((int) $deviceId, [
                'event' => $validated['is_active'] ? 'schedule_enabled' : 'schedule_disabled',
                'name' => $existing['name'] ?? 'Schedule',
                'start_time' => $existing['start_time'] ?? null,
                'end_time' => $existing['end_time'] ?? null,
                'status' => $existing['status'] ?? null,
            ]);
            return response()->json($existing);
        }

        return response()->json(['message' => 'Failed to update schedule'], 500);
    }

    /**
     * Toggle active state of one schedule item.
     */
    public function activateItem(Request $request, $deviceId, $scheduleId)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $existing = $this->resolveSchedule((int) $deviceId, (string) $scheduleId);
        if (!$existing) {
            return response()->json(['message' => 'Schedule not found'], 404);
        }

        $startAt = isset($existing['start_time']) ? Carbon::parse($existing['start_time']) : null;
        $endAt = isset($existing['end_time']) ? Carbon::parse($existing['end_time']) : null;
        $now = now();

        if ($validated['is_active'] && $startAt && $endAt) {
            $daysOfWeek = $this->extractDaysOfWeekFromSchedule($existing, $startAt->utc());
            if (!$this->hasNoOverlap((int) $deviceId, $startAt->utc(), $endAt->utc(), (string) $scheduleId, $daysOfWeek)) {
                return response()->json([
                    'message' => 'Cannot enable this schedule because it overlaps with another active schedule.',
                ], 422);
            }
        }

        $existing['is_active'] = (bool) $validated['is_active'];

        if ($existing['is_active'] === true) {
            if ($endAt && $now->gte($endAt)) {
                $existing['status'] = 'completed';
                $existing['is_active'] = false;
            } elseif ($startAt && $endAt && $now->gte($startAt) && $now->lt($endAt)) {
                $existing['status'] = 'active';
                $this->firebase->setControl((int) $deviceId, true);
            } else {
                $existing['status'] = 'pending';
            }
        } else {
            $existing['status'] = ($endAt && $now->gte($endAt)) ? 'completed' : 'pending';
        }

        $saved = $this->persistSchedule((int) $deviceId, (string) $scheduleId, $existing, $existing);
        if (!$saved) {
            return response()->json(['message' => 'Failed to update schedule'], 500);
        }

        $this->firebase->appendScheduleHistory((int) $deviceId, [
            'event' => $validated['is_active'] ? 'schedule_enabled' : 'schedule_disabled',
            'name' => $existing['name'] ?? 'Schedule',
            'start_time' => $existing['start_time'] ?? null,
            'end_time' => $existing['end_time'] ?? null,
            'status' => $existing['status'] ?? null,
            'schedule_id' => (string) $scheduleId,
        ]);

        $existing['id'] = (string) $scheduleId;
        return response()->json($existing);
    }

    private function getDeviceSchedules(int $deviceId): array
    {
        $items = $this->firebase->getScheduleItems($deviceId);
        if (!empty($items)) {
            return array_values(array_map(function ($item) {
                $item['id'] = (string) ($item['id'] ?? '');
                return $item;
            }, $items));
        }

        $legacy = $this->firebase->getSchedule($deviceId);
        if (!$legacy) {
            return [];
        }

        $legacy['id'] = self::LEGACY_SCHEDULE_ID;
        return [$legacy];
    }

    private function resolveSchedule(int $deviceId, string $scheduleId): ?array
    {
        if ($scheduleId === self::LEGACY_SCHEDULE_ID) {
            return $this->firebase->getSchedule($deviceId);
        }

        return $this->firebase->getScheduleItem($deviceId, $scheduleId);
    }

    private function persistSchedule(int $deviceId, string $scheduleId, array $existing, array $data): bool
    {
        if ($scheduleId === self::LEGACY_SCHEDULE_ID) {
            return $this->firebase->updateSchedule($deviceId, $data);
        }

        return $this->firebase->updateScheduleItem($deviceId, $scheduleId, $data);
    }

    private function hasNoOverlap(int $deviceId, Carbon $startAt, Carbon $endAt, ?string $ignoreScheduleId = null, ?array $daysOfWeek = null): bool
    {
        $schedules = $this->getDeviceSchedules($deviceId);
        $newDays = $this->normalizeDaysOfWeek($daysOfWeek);
        $newRecurring = !empty($newDays);

        $newStartMinute = $this->minutesOfDay($startAt);
        $newEndMinute = $this->minutesOfDay($endAt);

        foreach ($schedules as $schedule) {
            $scheduleId = (string) ($schedule['id'] ?? '');
            if ($ignoreScheduleId !== null && $scheduleId === $ignoreScheduleId) {
                continue;
            }

            if (!($schedule['is_active'] ?? true)) {
                continue;
            }

            if (($schedule['status'] ?? 'pending') === 'completed') {
                continue;
            }

            $existingStart = isset($schedule['start_time']) ? Carbon::parse($schedule['start_time'])->utc() : null;
            $existingEnd = isset($schedule['end_time']) ? Carbon::parse($schedule['end_time'])->utc() : null;
            if (!$existingStart || !$existingEnd) {
                continue;
            }

            $existingDays = $this->extractDaysOfWeekFromSchedule($schedule, $existingStart);
            $existingRecurring = !empty($existingDays);

            if (!$newRecurring && !$existingRecurring) {
                if ($startAt->lt($existingEnd) && $endAt->gt($existingStart)) {
                    return false;
                }
                continue;
            }

            $normalizedNewDays = $newRecurring ? $newDays : [$startAt->dayOfWeek];
            $normalizedExistingDays = $existingRecurring ? $existingDays : [$existingStart->dayOfWeek];
            if (empty(array_intersect($normalizedNewDays, $normalizedExistingDays))) {
                continue;
            }

            $existingStartMinute = $this->minutesOfDay($existingStart);
            $existingEndMinute = $this->minutesOfDay($existingEnd);
            if ($this->timeRangesOverlap($newStartMinute, $newEndMinute, $existingStartMinute, $existingEndMinute)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeDaysOfWeek(?array $days): array
    {
        if (!is_array($days)) {
            return [];
        }

        $normalized = [];
        foreach ($days as $day) {
            $intDay = (int) $day;
            if (in_array($intDay, self::VALID_DAYS_OF_WEEK, true)) {
                $normalized[] = $intDay;
            }
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);
        return $normalized;
    }

    private function extractDaysOfWeekFromSchedule(array $schedule, Carbon $fallbackStart): array
    {
        $days = $this->normalizeDaysOfWeek($schedule['days_of_week'] ?? null);
        if (!empty($days)) {
            return $days;
        }

        return [$fallbackStart->dayOfWeek];
    }

    private function minutesOfDay(Carbon $time): int
    {
        return ($time->hour * 60) + $time->minute;
    }

    private function timeRangesOverlap(int $startA, int $endA, int $startB, int $endB): bool
    {
        return $startA < $endB && $endA > $startB;
    }
}
