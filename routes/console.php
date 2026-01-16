<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the WatchNewFiles command to run every five minutes
Schedule::command('files:watch')
    ->everyFiveMinutes()
    ->withoutOverlapping(15); // Prevents overlapping runs within 15 minutes


 
Schedule::command('db:backup --type=auto')
    ->cron(function () {
        // Read settings from .env
        $time  = explode(':', env('BACKUP_TIME', '00:00'));
        $hour  = $time[0];
        $min   = $time[1] ?? '00';
        
        $dayOfWeek = env('BACKUP_DAY', '*');
        $month     = env('BACKUP_MONTH', '*');

        // Cron Format: minute hour day(month) month day(week)
        // Example: "00 14 * * 1" (Every Monday at 14:00)
        return "{$min} {$hour} * {$month} {$dayOfWeek}";
    })
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log')); // Log to be read on the view