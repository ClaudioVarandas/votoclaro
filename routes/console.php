<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Not in the background: production runs the scheduler as a systemd oneshot service, which
// stops every process it started once schedule:run returns.
Schedule::command('ar:initiatives:import')
    ->dailyAt('01:30');

Schedule::command('ar:budget:import')
    ->dailyAt('02:00');
