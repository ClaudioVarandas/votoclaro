<?php

namespace Tests\Feature;

use App\Enums\BudgetProposalStatus;
use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\BudgetTopic;
use App\Models\BudgetVote;
use App\Models\Initiative;
use App\Models\Legislature;
use App\Models\Vote;
use App\Models\VotePosition;
use App\Services\BudgetStatsService;
use App\View\Components\HomeHero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_successful_response(): void
    {
        Legislature::factory()->xvii()->withParties()->create();

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.dashboard');
    }

    public function test_dashboard_displays_the_legislature_in_figures(): void
    {
        Initiative::factory()->approved()->count(3)->create();
        Initiative::factory()->rejected()->count(2)->create();

        $response = $this->get(route('dashboard'));

        $response->assertSee(__('ui.dashboard.numbers_title'));
        $response->assertSee(__('ui.dashboard.total_initiatives'));
        $response->assertSee(__('ui.dashboard.outcome.approved'));
        $response->assertSee(__('ui.dashboard.approval_rate'));
        $response->assertViewHas('approved', 3);
        $response->assertViewHas('rejected', 2);
    }

    public function test_dashboard_approval_rate_card_includes_unanimous_count(): void
    {
        $initiative = Initiative::factory()->approved()->create();
        Vote::factory()->create([
            'initiative_id' => $initiative->id,
            'unanimous' => true,
            'is_latest' => true,
        ]);
        Vote::factory()->create([
            'initiative_id' => $initiative->id,
            'unanimous' => false,
            'is_latest' => true,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertSee(__('ui.dashboard.unanimous_votes'));
        $response->assertViewHas('unanimousCount', 1);
        $response->assertViewHas('unanimousPct', 50.0);
    }

    public function test_dashboard_lists_every_party_with_its_initiatives_most_active_first(): void
    {
        Legislature::factory()->xvii()->withParties(['PS', 'PSD', 'CH', 'IL'])->create();
        Initiative::factory()->parliamentaryGroup('PS')->approved()->count(8)->create();
        Initiative::factory()->parliamentaryGroup('PS')->rejected()->count(2)->create();
        Initiative::factory()->parliamentaryGroup('CH')->rejected()->count(12)->create();
        Initiative::factory()->parliamentaryGroup('PSD')->approved()->count(3)->create();

        $response = $this->get(route('dashboard'));

        $response->assertSee(__('ui.dashboard.parties_title'));
        $response->assertViewHas('partyTable', function (array $parties): bool {
            $byParty = collect($parties)->keyBy('acronym');

            return array_column($parties, 'acronym') === ['CH', 'PS', 'PSD', 'IL']
                && $byParty['PS']['initiatives'] === 10
                && $byParty['PS']['approved'] === 8
                && $byParty['PS']['approval_rate'] === 80.0
                && $byParty['IL']['initiatives'] === 0;
        });
        $response->assertSee('80,0%');
    }

    public function test_dashboard_party_table_shows_how_each_party_voted(): void
    {
        Legislature::factory()->xvii()->withParties(['PS'])->create();
        $initiative = Initiative::factory()->create();
        foreach (['favor', 'favor', 'contra'] as $position) {
            $vote = Vote::factory()->create(['initiative_id' => $initiative->id]);
            VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'PS', 'position' => $position]);
        }

        $this->get(route('dashboard'))
            ->assertSee(__('ui.dashboard.votes_column'))
            ->assertSee('67% a favor')
            ->assertSee('A favor: 2 · Contra: 1 · Abstenção: 0');
    }

    public function test_dashboard_compares_the_government_with_the_parliamentary_groups(): void
    {
        Initiative::factory()->government()->approved()->count(3)->create(['days_to_approval' => 40]);
        Initiative::factory()->government()->rejected()->create(['days_to_approval' => null]);
        Initiative::factory()->parliamentaryGroup('PS')->rejected()->count(6)->create();

        $response = $this->get(route('dashboard'));

        $response->assertSee(__('ui.dashboard.authorship_title'));
        $response->assertViewHas('authorship', fn (array $authorship): bool => $authorship['government'] === ['total' => 4, 'approved' => 3, 'approval_rate' => 75.0, 'avg_days' => 40]
            && $authorship['parliamentary_group']['total'] === 6
            && $authorship['parliamentary_group']['approval_rate'] === 0.0);
        $response->assertSee('75,0% aprovadas');
        $response->assertSee('40 dias, em média, até à aprovação');
    }

    public function test_dashboard_numbers_are_written_the_portuguese_way(): void
    {
        Initiative::factory()->approved()->count(1)->create();
        Initiative::factory()->rejected()->count(2)->create();

        $this->get(route('dashboard'))
            ->assertSee('33,3%')
            ->assertDontSee('33.3%');
    }

    public function test_dashboard_party_grid_links_to_party_pages(): void
    {
        Legislature::factory()->xvii()->withParties()->create();

        $response = $this->get(route('dashboard'));

        $response->assertSee(route('parties.show', 'ps'));
        $response->assertSee(route('parties.show', 'psd'));
    }

    public function test_dashboard_displays_latest_votes_table(): void
    {
        $initiative = Initiative::factory()->approved()->create([
            'title' => 'Test Initiative Title',
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertSee(__('ui.dashboard.latest_votes'));
        $response->assertSee('Test Initiative Title');
    }

    public function test_dashboard_renders_with_empty_database(): void
    {
        Legislature::factory()->xvii()->withParties()->create();

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee(__('ui.dashboard.no_votes'));
    }

    public function test_the_hero_puts_first_the_budget_being_voted(): void
    {
        $legislature = Legislature::factory()->xvii()->withParties()->create();
        $this->budgetWith($legislature, 2026, [BudgetProposalStatus::Rejected], votes: 1);
        $voting = $this->budgetWith($legislature, 2027, [BudgetProposalStatus::Rejected, BudgetProposalStatus::Pending], votes: 1);
        BudgetTopic::factory()->create(['budget_id' => $voting->id, 'label' => 'Habitação', 'slug' => 'habitacao', 'parties_count' => 5]);

        $this->get(route('dashboard'))
            ->assertSee(__('ui.home.badge.voting'))
            ->assertSee(__('ui.home.title.voting', ['year' => 2027]))
            ->assertSee(__('ui.budget.voting_progress', ['voted' => 1, 'total' => 2]))
            ->assertSee(route('budgets.show', 2027), false)
            ->assertSee(route('budgets.proposals.index', 2027), false)
            ->assertSee(route('budgets.topics.show', ['year' => 2027, 'topic' => 'habitacao']), false)
            ->assertDontSee(__('ui.home.title.closed', ['year' => 2026]));
    }

    public function test_the_hero_says_when_the_amendments_are_in_but_not_voted_yet(): void
    {
        $legislature = Legislature::factory()->xvii()->withParties()->create();
        $this->budgetWith($legislature, 2027, [BudgetProposalStatus::Pending, BudgetProposalStatus::Pending]);

        $this->get(route('dashboard'))
            ->assertSee(__('ui.home.badge.submitted'))
            ->assertSee(__('ui.home.title.submitted', ['year' => 2027]))
            ->assertSee(__('ui.home.lead.submitted', ['count' => 2]))
            ->assertDontSee(__('ui.home.badge.voting'));
    }

    public function test_the_hero_closes_the_budget_with_the_final_global_vote_of_its_bill(): void
    {
        $legislature = Legislature::factory()->xvii()->withParties()->create();
        $budget = $this->budgetWith($legislature, 2027, [BudgetProposalStatus::Approved, BudgetProposalStatus::Rejected, BudgetProposalStatus::Pending], votes: 2);
        $this->billVote($budget, Vote::PHASE_FINAL_GLOBAL, 'Aprovado', '2026-11-27');

        $this->get(route('dashboard'))
            ->assertSee(__('ui.home.badge.closed'))
            ->assertSee(__('ui.home.title.closed', ['year' => 2027]))
            ->assertSee('Aprovado em votação final global a 27/11/2026.')
            ->assertSee(__('ui.home.lead.closed', ['count' => 3, 'approved' => 1]))
            ->assertDontSee(__('ui.budget.overview.pending'))
            ->assertDontSee(__('ui.budget.voting_progress', ['voted' => 2, 'total' => 3]));
    }

    public function test_the_hero_says_when_the_bill_was_rejected(): void
    {
        $legislature = Legislature::factory()->xvii()->withParties()->create();
        $budget = $this->budgetWith($legislature, 2027, [BudgetProposalStatus::Rejected], votes: 1);
        $this->billVote($budget, Vote::PHASE_FINAL_GLOBAL, 'Rejeitado', '2026-11-27');

        $this->get(route('dashboard'))->assertSee('Chumbado em votação final global a 27/11/2026.');
    }

    public function test_the_vote_in_general_does_not_close_the_budget(): void
    {
        $legislature = Legislature::factory()->xvii()->withParties()->create();
        $budget = $this->budgetWith($legislature, 2027, [BudgetProposalStatus::Rejected, BudgetProposalStatus::Pending], votes: 1);
        $this->billVote($budget, 'Votação na generalidade', 'Aprovado', '2026-10-28');

        $this->assertSame(BudgetStatsService::STAGE_VOTING, app(BudgetStatsService::class)->stage($budget->fresh('initiative')));
    }

    public function test_a_budget_with_nothing_left_to_vote_counts_as_closed_without_the_final_vote_on_record(): void
    {
        $legislature = Legislature::factory()->xvii()->withParties()->create();
        $budget = $this->budgetWith($legislature, 2026, [BudgetProposalStatus::Approved], votes: 1);

        $this->assertSame(BudgetStatsService::STAGE_CLOSED, app(BudgetStatsService::class)->stage($budget));
        $this->get(route('dashboard'))
            ->assertSee(__('ui.home.title.closed', ['year' => 2026]))
            ->assertDontSee('em votação final global');
    }

    public function test_the_hero_introduces_the_site_without_a_budget(): void
    {
        Legislature::factory()->xvii()->withParties()->create();

        $this->get(route('dashboard'))
            ->assertSee(__('ui.home.intro_title'))
            ->assertSee(route('initiatives.index'), false);
    }

    public function test_the_hero_draws_a_sample_of_the_real_vote_grid(): void
    {
        $legislature = Legislature::factory()->xvii()->withParties()->create();
        $budget = Budget::factory()->create(['legislature_id' => $legislature->id, 'year' => 2027, 'party_order' => ['PSD', 'PS']]);
        $proposal = BudgetProposal::factory()->create(['budget_id' => $budget->id, 'status' => BudgetProposalStatus::Pending]);
        $vote = BudgetVote::factory()->create(['budget_proposal_id' => $proposal->id, 'is_final' => true]);
        $vote->positions()->create(['party' => 'PSD', 'position' => 'contra']);
        $vote->positions()->create(['party' => 'PS', 'position' => 'favor']);

        $highlight = app(BudgetStatsService::class)->highlight($legislature);

        $this->assertSame(['parties' => ['PSD', 'PS'], 'rows' => [['contra', 'favor']]], $highlight['sample']);
        $this->get(route('dashboard'))->assertSee(__('ui.home.sample_caption'));
    }

    public function test_the_hero_component_reads_its_figures(): void
    {
        $budget = Budget::factory()->create(['year' => 2027]);
        $hero = app()->make(HomeHero::class, ['highlight' => [
            'budget' => $budget->fresh('legislature'),
            'stage' => BudgetStatsService::STAGE_VOTING,
            'final_vote' => null,
            'overview' => ['total' => 2919, 'approved' => 210, 'rejected' => 1330, 'pending' => 1301, 'voted' => 1539],
            'topics' => collect(),
            'sample' => ['parties' => ['PSD', 'PS', 'CH'], 'rows' => [['favor', null, 'split']]],
        ]]);

        $this->assertEqualsWithDelta(1539 / 2919, $hero->votedShare(), 0.0001);
        $this->assertSame('2.919', $hero->figure(2919));
        $this->assertSame('grid-cols-[repeat(3,2rem)]', $hero->sampleColumnsClass());
        $this->assertSame('±', $hero->symbol('split'));
        $this->assertSame('', $hero->symbol(null));
        $this->assertStringContainsString('republic', $hero->cellClasses('favor'));
        $this->assertTrue($hero->showsSample());
        $this->assertFalse(app()->make(HomeHero::class, ['highlight' => null])->hasBudget());
        $this->assertTrue($hero->isVoting());
        $this->assertSame(['approved', 'rejected', 'pending'], array_keys($hero->figures()));
    }

    /**
     * @param  list<BudgetProposalStatus>  $statuses  one amendment each
     */
    private function budgetWith(Legislature $legislature, int $year, array $statuses, int $votes = 0): Budget
    {
        $bill = Initiative::factory()->create(['legislature_id' => $legislature->id, 'title' => "Orçamento do Estado para {$year}"]);
        $budget = Budget::factory()->create(['legislature_id' => $legislature->id, 'year' => $year, 'initiative_id' => $bill->id]);

        foreach ($statuses as $index => $status) {
            $proposal = BudgetProposal::factory()->create(['budget_id' => $budget->id, 'status' => $status]);

            if ($index < $votes) {
                BudgetVote::factory()->create(['budget_proposal_id' => $proposal->id, 'is_final' => true]);
            }
        }

        return $budget;
    }

    private function billVote(Budget $budget, string $phase, string $result, string $date): void
    {
        Vote::factory()->create(['initiative_id' => $budget->initiative_id, 'phase' => $phase, 'result' => $result, 'date' => $date]);
    }
}
