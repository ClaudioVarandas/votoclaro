<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Legislature;
use App\Services\LegislatureContext;
use App\View\Components\BudgetAgreementMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetAgreementMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Budget $budget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->budget = Budget::factory()->create(['legislature_id' => Legislature::factory()->xvii()->withParties()->create()->id, 'year' => 2027]);
    }

    public function test_each_cell_is_shaded_by_how_often_the_pair_voted_alike(): void
    {
        $matrix = $this->matrix();

        $this->assertSame('98%', $matrix->percent('PSD', 'CDS-PP'));
        $this->assertStringContainsString('bg-republic-600', $matrix->shadeClass('PSD', 'CDS-PP'));
        $this->assertStringContainsString('bg-republic-200', $matrix->shadeClass('PS', 'CH'));
        $this->assertStringContainsString('bg-sand-100', $matrix->shadeClass('PSD', 'PS'));
        $this->assertSame('—', $matrix->percent('PS', 'JPP'));
        $this->assertStringContainsString('bg-sand-50', $matrix->shadeClass('PS', 'JPP'));
    }

    public function test_a_cell_links_to_the_amendments_the_pair_voted_apart_and_describes_the_count(): void
    {
        $matrix = $this->matrix();

        $this->assertSame(route('budgets.proposals.index', ['year' => 2027, 'divergem' => 'ps,ch']), $matrix->url('PS', 'CH'));
        $this->assertSame(40, $matrix->apartCount('PS', 'CH'));
        $this->assertSame('PS e CH votaram igual em 50 das 90 propostas que ambos votaram', $matrix->describe('PS', 'CH'));
        $this->assertSame('PS e JPP votaram igual em 5 das 8 propostas que ambos votaram', $matrix->describe('PS', 'JPP'));
    }

    public function test_the_phone_list_ranks_the_others_from_the_closest_and_leaves_pairs_without_a_rate_last(): void
    {
        $this->assertSame(['CDS-PP', 'CH', 'PS', 'JPP'], $this->matrix()->closestTo('PSD'));
        $this->assertSame(['CH', 'PSD', 'CDS-PP', 'JPP'], $this->matrix()->closestTo('PS'));
    }

    public function test_it_renders_only_with_two_parties_or_more(): void
    {
        $this->assertTrue($this->matrix()->shouldRender());
        $this->assertFalse((new BudgetAgreementMatrix(app(LegislatureContext::class), $this->budget, ['parties' => ['PS'], 'pairs' => []]))->shouldRender());
    }

    private function matrix(): BudgetAgreementMatrix
    {
        $pairs = [];
        $set = function (string $first, string $second, int $voted, int $same, ?float $rate) use (&$pairs): void {
            $pairs[$first][$second] = $pairs[$second][$first] = ['voted' => $voted, 'same' => $same, 'rate' => $rate];
        };

        $set('PSD', 'CDS-PP', 100, 98, 98.0);
        $set('PSD', 'CH', 100, 30, 30.0);
        $set('PSD', 'PS', 100, 9, 9.0);
        $set('PSD', 'JPP', 8, 1, null);
        $set('PS', 'CH', 90, 50, 55.6);
        $set('PS', 'CDS-PP', 100, 8, 8.0);
        $set('PS', 'JPP', 8, 5, null);

        return new BudgetAgreementMatrix(app(LegislatureContext::class), $this->budget, [
            'parties' => ['PSD', 'CH', 'PS', 'CDS-PP', 'JPP'],
            'pairs' => $pairs,
        ]);
    }
}
