<?php

use App\Models\SiteSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (! Schema::hasTable('site_settings')) {
    return;
}

$settings = SiteSetting::first();

$scheduleType = $settings->schedule_type ?? 'scheduled';

// Если выбран Cron, Laravel Schedule не планирует задачу — управление через системный cron
if ($scheduleType === 'scheduled') {
    $scheduleFrequency = $settings->schedule_frequency ?? 'everyMinute';
    $scheduleTimezone = $settings->schedule_timezone ?? 'Asia/Bishkek';
    $scheduleWithoutOverlapping = $settings->schedule_without_overlapping ?? true;

    // Schedule a single command that processes all profiles sequentially with delays
    // This prevents parallel API requests that cause HTTP 429 rate limiting
    $scheduleItem = Schedule::command('ebay:import-laptops --scheduled')
        ->timezone($scheduleTimezone)
        ->appendOutputTo(storage_path('logs/ebay-scheduler.log'));

    // Apply frequency
    $scheduleItem = match ($scheduleFrequency) {
        'everyMinute' => $scheduleItem->everyMinute(),
        'everyFiveMinutes' => $scheduleItem->everyFiveMinutes(),
        'everyTenMinutes' => $scheduleItem->everyTenMinutes(),
        'everyFifteenMinutes' => $scheduleItem->everyFifteenMinutes(),
        'everyThirtyMinutes' => $scheduleItem->everyThirtyMinutes(),
        'hourly' => $scheduleItem->hourly(),
        default => $scheduleItem->everyMinute(),
    };

    if ($scheduleWithoutOverlapping) {
        $scheduleItem->withoutOverlapping();
    }
}
