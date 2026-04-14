<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run device schedule checker every minute
Schedule::command('schedule:run-device-schedules')->everyMinute()->withoutOverlapping();

// Run device monitoring every minute for real-time alerts
Schedule::command('devices:monitor')->everyMinute()->withoutOverlapping();

// Reset threshold counters on their period boundaries
Schedule::command('energy:reset-period daily')->dailyAt('00:00')->withoutOverlapping();
Schedule::command('energy:reset-period weekly')->weeklyOn(1, '00:00')->withoutOverlapping();
Schedule::command('energy:reset-period monthly')->monthlyOn(1, '00:00')->withoutOverlapping();
