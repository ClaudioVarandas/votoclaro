<?php

use App\Models\Budget;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Not in the background: production runs the scheduler as a systemd oneshot service, which
// stops every process it started once schedule:run returns. The output of the imports goes to
// a log of its own, as a failed run says why only there.
Schedule::command('ar:initiatives:import')
    ->dailyAt('01:30')
    ->appendOutputTo(storage_path('logs/schedule-imports.log'));

Schedule::command('ar:budget:import')
    ->dailyAt('02:00')
    ->appendOutputTo(storage_path('logs/schedule-imports.log'));

// From October to December the budget for the next year is voted, and its results keep coming
// for days after the final vote: that budget is imported every half hour through the day too.
Schedule::command('ar:budget:import', ['--year' => now()->year + 1])
    ->everyThirtyMinutes()
    ->between('08:00', '23:59')
    ->when(fn (): bool => now()->month >= 10 && Budget::query()->where('year', now()->year + 1)->exists())
    ->withoutOverlapping(60)
    ->appendOutputTo(storage_path('logs/schedule-imports.log'));
