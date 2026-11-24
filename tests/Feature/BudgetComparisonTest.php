<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Legislature;
use App\View\Components\BudgetComparison;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetComparisonTest extends TestCase
{
    use RefreshDatabase;

    private Legislature $legislature;

    protected function setUp(): void
    {
        parent::setUp();

        $this->legislature = Legislature::factory()->xvii()->withParties()->create();
    }

    public function test_the_totals_show_the_difference_in_count_share_or_points(): void
    {
        $rows = collect($this->comparison()->totalRows())->keyBy('label');

        $this->assertSame(['label' => 'Propostas de alteração', 'current' => '2.919', 'previous' => '2.193', 'delta' => '+726 (+33%)', 'direction' => 'up', 'arrow' => '▲'], $rows['Propostas de alteração']);
        $this->assertSame('+4,7 p.p.', $rows['Taxa de aprovação']['delta']);
        $this->assertSame('12,5%', $rows['Taxa de aprovação']['current']);
        $this->assertSame('−50 (−20%)', $rows['Rejeitadas']['delta']);
        $this->assertSame('down', $rows['Rejeitadas']['direction']);
        $this->assertSame('0', $rows['Aprovadas']['delta']);
        $this->assertSame('same', $rows['Aprovadas']['direction']);
    }

    public function test_the_pace_rows_share_one_scale_and_the_summary_names_the_last_day_voted(): void
    {
        $component = $this->comparison();
        $rows = $component->paceRows();

        $this->assertTrue($component->hasPace());
        $this->assertSame(['day' => 1, 'current' => 59, 'previous' => 32, 'current_share' => 59 / 170, 'previous_share' => 32 / 170], $rows[0]);
        $this->assertSame(['day' => 3, 'current' => null, 'previous' => 170, 'current_share' => 0.0, 'previous_share' => 1.0], $rows[2]);
        $this->assertSame('Ao fim de 2 dias de votação, 168 aprovadas; no OE 2026, ao fim de 2 dias, 73.', $component->paceSummary());
    }

    public function test_without_votes_in_both_years_there_is_no_pace(): void
    {
        $component = $this->comparison(pace: [['current' => null, 'previous' => ['decided' => 10, 'approved' => 2]]]);

        $this->assertFalse($component->hasPace());
        $this->assertNull($component->paceSummary());
    }

    public function test_the_party_rows_mark_a_party_missing_in_a_year(): void
    {
        $rows = collect($this->comparison()->partyRows())->keyBy('acronym');

        $this->assertSame(['acronym' => 'PS', 'proposed' => '210', 'proposed_before' => '123', 'rate' => '55,7%', 'rate_before' => '30,1%', 'rate_direction' => 'up'], $rows['PS']);
        $this->assertSame('—', $rows['JPP']['proposed_before']);
        $this->assertSame('same', $rows['JPP']['rate_direction']);
    }

    public function test_it_notes_a_year_before_voted_in_another_legislature(): void
    {
        $this->assertNull($this->comparison()->legislatureNote());

        $older = Legislature::factory()->create(['sigla' => 'XVI', 'number' => 16]);
        $this->assertSame(
            'O OE 2026 foi votado na XVI Legislatura, com outro Parlamento e outros grupos parlamentares.',
            $this->comparison(previousLegislature: $older)->legislatureNote(),
        );
    }

    public function test_it_renders_only_with_a_comparison(): void
    {
        $this->assertTrue($this->comparison()->shouldRender());
        $this->assertFalse((new BudgetComparison(Budget::factory()->make(['legislature_id' => $this->legislature->id]), null))->shouldRender());
    }

    /**
     * @param  list<array{current: ?array{decided: int, approved: int}, previous: ?array{decided: int, approved: int}}>|null  $pace
     */
    private function comparison(?array $pace = null, ?Legislature $previousLegislature = null): BudgetComparison
    {
        $budget = Budget::factory()->make(['legislature_id' => $this->legislature->id, 'year' => 2027]);
        $previous = Budget::factory()->make(['legislature_id' => ($previousLegislature ?? $this->legislature)->id, 'year' => 2026]);

        return new BudgetComparison($budget, [
            'previous' => $previous,
            'totals' => [
                ['key' => 'total', 'current' => 2919, 'previous' => 2193],
                ['key' => 'approved', 'current' => 170, 'previous' => 170],
                ['key' => 'approval_rate', 'current' => 12.5, 'previous' => 7.8],
                ['key' => 'rejected', 'current' => 200, 'previous' => 250],
            ],
            'parties' => [
                ['acronym' => 'PS', 'current' => ['proposed' => 210, 'approved' => 117, 'approval_rate' => 55.7], 'previous' => ['proposed' => 123, 'approved' => 37, 'approval_rate' => 30.1]],
                ['acronym' => 'JPP', 'current' => ['proposed' => 187, 'approved' => 12, 'approval_rate' => 6.4], 'previous' => null],
            ],
            'pace' => $pace ?? [
                ['current' => ['decided' => 543, 'approved' => 59], 'previous' => ['decided' => 499, 'approved' => 32]],
                ['current' => ['decided' => 1220, 'approved' => 168], 'previous' => ['decided' => 911, 'approved' => 73]],
                ['current' => null, 'previous' => ['decided' => 2148, 'approved' => 170]],
            ],
        ]);
    }
}
