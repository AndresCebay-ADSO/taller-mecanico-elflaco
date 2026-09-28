<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Revision nocturna de consistencia stock vs lotes (solo reporta, no corrige).
Schedule::command('inventory:check')
    ->dailyAt('23:30')
    ->appendOutputTo(storage_path('logs/inventory-check.log'));
