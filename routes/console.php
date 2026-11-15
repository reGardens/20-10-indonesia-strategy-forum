<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule sitemap generation to run weekly
Illuminate\Support\Facades\Schedule::command('sitemap:generate')->weekly();

// Tandai iklan yang sudah lewat tanggal selesai sebagai expired (butuh cron `schedule:run` tiap menit di hosting).
Illuminate\Support\Facades\Schedule::command('ads:expire')->hourly();
