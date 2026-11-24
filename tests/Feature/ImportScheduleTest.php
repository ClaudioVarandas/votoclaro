<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Legislature;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule as ScheduleFacade;
use Tests\TestCase;

class ImportScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_daily_imports_keep_their_output_in_a_log(): void
    {
        foreach (['ar:initiatives:import' => '30 1 * * *', 'ar:budget:import' => '0 2 * * *'] as $command => $expression) {
            $event = $this->event(fn (Event $event): bool => str_ends_with($event->command, "'artisan' {$command}"));

            $this->assertSame($expression, $event->expression);
            $this->assertSame(storage_path('logs/schedule-imports.log'), $event->output);
            $this->assertTrue($event->shouldAppendOutput);
        }
    }

    public function test_the_budget_of_next_year_is_imported_every_half_hour_from_october(): void
    {
        Budget::factory()->create(['legislature_id' => Legislature::factory()->xvii()->create()->id, 'year' => 2027]);

        $this->travelTo('2026-11-24 14:30:00');
        $event = $this->halfHourly();
        $this->assertStringContainsString('--year=2027', $event->command);
        $this->assertTrue($event->isDue($this->app));
        $this->assertTrue($event->filtersPass($this->app));
        $this->assertSame(storage_path('logs/schedule-imports.log'), $event->output);
        $this->assertTrue($event->withoutOverlapping);

        $this->travelTo('2026-11-24 03:00:00');
        $this->assertFalse($this->halfHourly()->filtersPass($this->app));

        $this->travelTo('2026-09-15 14:30:00');
        $this->assertFalse($this->halfHourly()->filtersPass($this->app));
    }

    public function test_without_the_budget_of_next_year_nothing_runs_every_half_hour(): void
    {
        $this->travelTo('2026-11-24 14:30:00');

        $this->assertFalse($this->halfHourly()->filtersPass($this->app));
    }

    /**
     * The schedule is defined afresh for each run, as production starts a new process each minute.
     */
    private function halfHourly(): Event
    {
        return $this->event(fn (Event $event): bool => str_contains($event->command, 'ar:budget:import --year='));
    }

    private function event(callable $matches): Event
    {
        $this->app->forgetInstance(Schedule::class);
        ScheduleFacade::clearResolvedInstances();
        $schedule = $this->app->make(Schedule::class);
        require base_path('routes/console.php');

        return collect($schedule->events())->first($matches) ?? $this->fail('No such scheduled event.');
    }
}
