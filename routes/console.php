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