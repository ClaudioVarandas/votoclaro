<?php

namespace Tests\Feature;

use App\Enums\BudgetVoteStage;
use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\BudgetVote;
use App\Models\Legislature;
use App\Services\BudgetMatrixService;
use App\View\Components\BudgetVoteMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetVoteMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Budget $budget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->budget = Budget::factory()->create(['legislature_id' => Legislature::factory()->xvii()->withParties()->create()->id]);
    }

    public function test_a_party_cell_is_its_position_in_the_decisive_vote(): void
    {
        $proposal = $this->proposal();
        $this->vote($proposal, 1, ['PSD' => 'favor', 'PS' => 'contra'], final: false);
        $this->vote($proposal, 2, ['PSD' => 'contra', 'PS' => 'contra']);

        $this->assertSame([$proposal->id => ['PSD' => 'contra', 'PS' => 'contra']], $this->cells());
    }

    public function test_a_party_that_voted_the_parts_differently_is_split(): void
    {
        $proposal = $this->proposal();
        $this->vote($proposal, 1, ['PSD' => 'favor', 'PS' => 'favor']);
        $this->vote($proposal, 2, ['PSD' => 'contra', 'PS' => 'favor']);

        $this->assertSame([$proposal->id => ['PSD' => BudgetMatrixService::SPLIT, 'PS' => 'favor']], $this->cells());
    }

    public function test_a_deputy_voting_apart_does_not_change_the_party_cell(): void
    {
        $proposal = $this->proposal();
        $vote = $this->vote($proposal, 1, ['PSD' => 'contra']);
        $vote->positions()->create(['party' => 'PSD', 'deputy' => 'Pedro Coelho', 'position' => 'favor']);

        $this->assertSame([$proposal->id => ['PSD' => 'contra']], $this->cells());
    }

    public function test_only_the_parties_that_voted_get_a_column(): void
    {
        $service = new BudgetMatrixService;

        $this->assertSame(['PSD', 'PS'], $service->votingParties([1 => ['PS' => 'favor'], 2 => ['PSD' => 'contra']], ['PSD', 'CH', 'PS', 'JPP']));
    }

    public function test_totals_count_each_position_and_leave_split_cells_out(): void
    {
        $totals = (new BudgetMatrixService)->totals([
            1 => ['PSD' => 'contra', 'PS' => 'favor'],
            2 => ['PSD' => 'contra', 'PS' => 'abstencao'],
            3 => ['PSD' => BudgetMatrixService::SPLIT],
        ], ['PSD', 'PS']);

        $this->assertSame(['favor' => 0, 'contra' => 2, 'abstencao' => 0], $totals['PSD']);
        $this->assertSame(['favor' => 1, 'contra' => 0, 'abstencao' => 1], $totals['PS']);
    }

    public function test_the_component_shows_a_symbol_and_a_spoken_label_for_each_cell(): void
    {
        $proposal = $this->proposal('Artigo 119.º-A (Saúde e direitos das mulheres na menopausa)');

        $view = $this->blade(
            '<x-budget-vote-matrix :budget="$budget" :proposals="$proposals" :parties="$parties" :cells="$cells" :totals="$totals" />',
            [
                'budget' => $this->budget,
                'proposals' => collect([$proposal]),
                'parties' => ['PSD', 'CH', 'PS', 'IL'],
                'cells' => [$proposal->id => ['PSD' => 'contra', 'CH' => 'abstencao', 'PS' => 'favor', 'IL' => BudgetMatrixService::SPLIT]],
                'totals' => ['PSD' => ['favor' => 0, 'contra' => 1, 'abstencao' => 0]],
            ],
        );

        $view->assertSee('Saúde e direitos das mulheres na menopausa')
            ->assertSee('PSD: Contra')
            ->assertSee('CH: Abstenção')
            ->assertSee('PS: A favor')
            ->assertSee('IL: '.__('ui.budget.position.split'))
            ->assertSee('✗')
            ->assertSee('✓')
            ->assertSee('±')
            ->assertSee(__('ui.budget.matrix.totals'));
    }

    public function test_the_totals_head_the_grid_with_a_row_for_each_position(): void
    {
        $proposals = BudgetProposal::factory()->count(3)->create(['budget_id' => $this->budget->id]);

        $view = $this->blade(
            '<x-budget-vote-matrix :budget="$budget" :proposals="$proposals" :parties="$parties" :cells="[]" :totals="$totals" />',
            [
                'budget' => $this->budget,
                'proposals' => BudgetProposal::query()->paginate(2),
                'parties' => ['PSD', 'PS'],
                'totals' => [
                    'PSD' => ['favor' => 1234, 'contra' => 2, 'abstencao' => 0],
                    'PS' => ['favor' => 0, 'contra' => 3, 'abstencao' => 1],
                ],
            ],
        );

        $this->assertCount(3, $proposals);
        $view->assertSeeInOrder(['<thead', __('ui.budget.matrix.totals'), '3 propostas, em todas as páginas', 'PSD', 'PS'], false)
            ->assertSeeInOrder(['<thead', 'A favor', '1.234', '0', 'Contra', '2', '3', 'Abstenção', '0', '1', __('ui.budget.matrix.proposal'), '</thead>', '<tbody'], false)
            ->assertDontSee('<tfoot', false);
    }

    public function test_the_totals_count_a_plain_list_of_amendments_too(): void
    {
        $this->assertSame(2, $this->matrixComponent(['PSD'], [], collect([$this->proposal(), $this->proposal()]))->totalProposals());
        $this->assertSame(['favor', 'contra', 'abstencao'], array_keys($this->matrixComponent(['PSD'], [])->totalRows()));
    }

    public function test_the_component_marks_a_party_that_did_not_vote(): void
    {
        $component = $this->matrixComponent(['JPP'], []);

        $this->assertNull($component->cell($this->proposal(), 'JPP'));
        $this->assertSame(__('ui.budget.position.none'), $component->label(null));
        $this->assertSame('', $component->symbol(null));
    }

    public function test_the_component_links_to_the_proposal_under_its_legislature(): void
    {
        $closed = Legislature::factory()->create(['sigla' => 'XVI', 'number' => 16]);
        $this->budget->update(['legislature_id' => $closed->id, 'year' => 2025]);
        $proposal = $this->proposal();

        $this->assertSame(
            url("/legislatura/XVI/orcamento/2025/propostas/{$proposal->slug}"),
            $this->matrixComponent(['PSD'], [])->proposalUrl($proposal->fresh()),
        );
    }

    public function test_the_component_names_the_proposer_when_no_party_matched(): void
    {
        $proposal = $this->proposal();
        $proposal->update(['proposer_label' => 'Vânia Jesus, Paulo Neves']);

        $this->assertSame('Vânia Jesus, Paulo Neves', $this->matrixComponent(['PSD'], [])->proposer($proposal->fresh()));
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function cells(): array
    {
        return app(BudgetMatrixService::class)->cells(BudgetProposal::query()->where('budget_id', $this->budget->id));
    }

    /**
     * @param  list<string>  $parties
     * @param  array<int, array<string, string>>  $cells
     * @param  iterable<BudgetProposal>  $proposals
     */
    private function matrixComponent(array $parties, array $cells, iterable $proposals = []): BudgetVoteMatrix
    {
        return app()->make(BudgetVoteMatrix::class, [
            'budget' => $this->budget->fresh('legislature'),
            'proposals' => $proposals,
            'parties' => $parties,
            'cells' => $cells,
        ]);
    }

    private function proposal(string $title = 'Artigo 1.º (Uma proposta)'): BudgetProposal
    {
        return BudgetProposal::factory()->create(['budget_id' => $this->budget->id, 'title' => $title]);
    }

    /**
     * @param  array<string, string>  $positions
     */
    private function vote(BudgetProposal $proposal, int $sequence, array $positions, bool $final = true): BudgetVote
    {
        $vote = BudgetVote::factory()->create([
            'budget_proposal_id' => $proposal->id,
            'sequence' => $sequence,
            'stage' => BudgetVoteStage::Committee,
            'is_final' => $final,
        ]);

        foreach ($positions as $party => $position) {
            $vote->positions()->create(['party' => $party, 'position' => $position]);
        }

        return $vote;
    }
}
