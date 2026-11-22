<?php

namespace Tests\Unit;

use App\Enums\InitiativeStatus;
use PHPUnit\Framework\TestCase;

class InitiativeStatusTest extends TestCase
{
    public function test_has_the_stored_values(): void
    {
        $this->assertSame(
            ['approved', 'rejected', 'in_progress', 'lapsed', 'withdrawn'],
            array_map(fn (InitiativeStatus $status): string => $status->value, InitiativeStatus::cases()),
        );
    }

    public function test_core_statuses(): void
    {
        $this->assertSame(
            [InitiativeStatus::Approved, InitiativeStatus::Rejected, InitiativeStatus::InProgress],
            InitiativeStatus::core(),
        );
    }

    public function test_listable_offers_only_the_core_statuses_when_nothing_lapsed_or_was_withdrawn(): void
    {
        $this->assertSame(InitiativeStatus::core(), InitiativeStatus::listable(['approved']));
        $this->assertSame(InitiativeStatus::core(), InitiativeStatus::listable([]));
    }

    public function test_listable_adds_lapsed_and_withdrawn_when_present(): void
    {
        $this->assertSame(
            [...InitiativeStatus::core(), InitiativeStatus::Lapsed],
            InitiativeStatus::listable(['lapsed', 'approved']),
        );
        $this->assertSame(
            InitiativeStatus::cases(),
            InitiativeStatus::listable(['withdrawn', 'lapsed']),
        );
    }

    public function test_every_status_has_badge_colors(): void
    {
        foreach (InitiativeStatus::cases() as $status) {
            $this->assertStringContainsString('bg-', $status->color());
            $this->assertStringContainsString('dark:', $status->color());
        }
    }
}
