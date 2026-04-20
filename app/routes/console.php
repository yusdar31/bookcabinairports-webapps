<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduler — Bookcabin
|--------------------------------------------------------------------------
|
| Schedule otomatis (dijalankan via cron: * * * * * php artisan schedule:run)
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ═══════════════════════════════════════════════════
//  SCHEDULER — Berjalan di background EC2
// ═══════════════════════════════════════════════════

// Auto check-out setiap 15 menit (grace: 30 menit toleransi)
Schedule::command('bookings:auto-checkout --grace=30')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/auto-checkout.log'));

// Reset kamar cleaning → available setiap jam (setelah 60 menit cleaning)
Schedule::command('rooms:reset-cleaning --minutes=60')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/room-reset.log'));
