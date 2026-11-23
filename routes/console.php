<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Backup harian (butuh: composer require spatie/laravel-backup dan cron schedule:run tiap menit).
 * Dijaga class_exists agar aplikasi tetap berjalan bila paket belum dipasang.
 * Panduan lengkap: docs/BACKUP-DAN-PEMULIHAN.md
 */
if (class_exists(\Spatie\Backup\BackupServiceProvider::class)) {
    Schedule::command('backup:clean')->dailyAt('01:00');
    Schedule::command('backup:run')->dailyAt('01:30');
    Schedule::command('backup:monitor')->dailyAt('03:00');
}
