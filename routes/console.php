<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keeps "hoy" and "ayer" fresh automatically. Requires something external to
// actually fire it — see the "Actualización automática" note in the project
// docs for how to wire this up with Windows Task Scheduler on this local setup.
Schedule::command('zennia:sync-today')
    ->dailyAt('23:55')
    ->withoutOverlapping()
    ->onOneServer();

// Keeps "hoy" continuously fresh throughout the shift, independent of anyone
// clicking "Actualizar". Also fired directly via Windows Task Scheduler.
Schedule::command('zennia:heartbeat')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();
