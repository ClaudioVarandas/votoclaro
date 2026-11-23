<?php

namespace Tests\Feature;

use App\Enums\BudgetVoteStage;
use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\BudgetTopic;
use App\Models\BudgetVote;
use App\Models\Legislature;
use App\Models\Party;
use App\Services\BudgetStatsService;
use App\Services\BudgetTopicService;
use App\View\Components\BudgetTopicList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTopicsTest extends TestCase
{
    use RefreshDatabase;

    private Legislature $current;

    private Budget $budget;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'budget_topics.min_proposals' => 2,
            'budget_topics.stopwords' => ['de', 'da', 'do', 'para', 'nas', 'reforço', 'apoio', 'artigo'],
            'budget_topics.generic' => [],
            'budget_topics.synonyms' => ['portagem' => 'Portagens', 'portagens' => 'Portagens'],
        ]);

        $this->current = Legislature::factory()->xvii()->withParties()->create();
        $this->budget = Budget::factory()->create(['legislature_id' => $this->current->id, 'year' => 2027]);
    }

    public function test_rebuilding_finds_the_topics_with_their_parties_votes_and_division(): void
    {
        $unanimous = $this->proposal('1C', 'Fim das portagens', ['PS'], ['PSD' => 'favor', 'PS' => 'favor', 'CH' => 'favor', 'IL' => 'favor']);
        $divided = $this->proposal('2C', 'Portagens nas pontes', ['CH'], ['PSD' => 'contra', 'PS' => 'contra', 'CH' => 'favor', 'IL' => 'favor']);
        $pending = $this->proposal('3C', 'Isenção de portagem', ['L']);
        $this->proposal('4C', 'Gatos de rua', ['PAN']);

        $found = app(BudgetTopicService::class)->rebuild($this->budget);

        $this->assertSame(1, $found);
        $topic = $this->budget->topics()->sole();
        $this->assertSame(['portagens', 'Portagens', 3, 3, 2], [$topic->slug, $topic->label, $topic->proposals_count, $topic->parties_count, $topic->voted_count]);
        $this->assertNull($topic->division, 'Two voted amendments are too few to measure division.');
        $this->assertEqualsCanonicalizing([$unanimous->id, $divided->id, $pending->id], $topic->proposals()->pluck('budget_proposals.id')->all());
    }

    public function test_the_division_of_a_topic_is_the_average_of_its_votes(): void
    {
        $this->proposal('1C', 'Portagens A', ['PS'], ['PSD' => 'favor', 'PS' => 'favor', 'CH' => 'favor', 'IL' => 'favor']);
        $this->proposal('2C', 'Portagens B', ['CH'], ['PSD' => 'contra', 'PS' => 'contra', 'CH' => 'favor', 'IL' => 'favor']);
        $this->proposal('3C', 'Portagens C', ['L'], ['PSD' => 'contra', 'PS' => 'favor', 'CH' => 'abstencao', 'IL' => 'abstencao']);

        app(BudgetTopicService::class)->rebuild($this->budget);

        $this->assertEqualsWithDelta((0 + 1 + 0.5) / 3, $this->budget->topics()->sole()->division, 0.0001);
    }

    public function test_division_runs_from_alike_to_evenly_split(): void
    {
        $service = app(BudgetTopicService::class);

        $this->assertSame(0.0, $service->division(['PSD' => 'favor', 'PS' => 'favor', 'CH' => 'favor']));
        $this->assertSame(1.0, $service->division(['PSD' => 'favor', 'PS' => 'contra', 'CH' => 'favor', 'IL' => 'contra']));
        $this->assertSame(0.5, $service->division(['PSD' => 'favor', 'PS' => 'contra', 'CH' => 'abstencao', 'IL' => 'abstencao']));
        $this->assertNull($service->division(['PSD' => 'favor', 'PS' => 'contra']), 'Two parties are too few.');
        $this->assertNull($service->division(['PSD' => 'split', 'PS' => 'favor', 'CH' => 'split']), 'A split vote is left out.');
    }

    public function test_rebuilding_again_replaces_the_topics(): void
    {
        $old = BudgetTopic::factory()->create(['budget_id' => $this->budget->id, 'slug' => 'antigo']);
        $this->proposal('1C', 'Portagens A');
        $this->proposal('2C', 'Portagens B');

        app(BudgetTopicService::class)->rebuild($this->budget);
        app(BudgetTopicService::class)->rebuild($this->budget);

        $this->assertModelMissing($old);
        $this->assertSame(['portagens'], $this->budget->topics()->pluck('slug')->all());
    }

    public function test_the_topics_command_rebuilds_a_budget(): void
    {
        $this->proposal('1C', 'Portagens A');
        $this->proposal('2C', 'Portagens B');

        $this->artisan('ar:budget:topics', ['--year' => 2027])
            ->expectsOutputToContain('2027: 1 topics found.')
            ->assertSuccessful();

        $this->assertSame(1, $this->budget->topics()->count());
    }

    public function test_the_topics_command_fails_without_a_budget(): void
    {
        $this->artisan('ar:budget:topics', ['--year' => 2030])->assertFailed();
    }

    public function test_the_ranking_draws_three_tops_from_the_topics_with_most_amendments(): void
    {
        $this->topic('Habitação', proposals: 70, parties: 10, voted: 40, division: 0.3);
        $this->topic('IRS', proposals: 85, parties: 9, voted: 50, division: 0.6);
        $this->topic('Pombais', proposals: 30, parties: 1, voted: 10, division: 0.9);
        $this->topic('Ferrovia', proposals: 50, parties: 8, voted: 3, division: 0.95);

        $topics = app(BudgetStatsService::class)->topics($this->budget);

        $this->assertSame(['Habitação', 'IRS', 'Ferrovia'], $topics['parties']->pluck('label')->all());
        $this->assertSame(['IRS', 'Habitação', 'Ferrovia'], $topics['proposals']->pluck('label')->all());
        $this->assertSame(['IRS', 'Habitação'], $topics['divided']->pluck('label')->all(), 'Too few votes on Ferrovia, one party on Pombais.');
    }

    public function test_the_budget_page_shows_the_top_of_topics_in_three_tabs(): void
    {
        $this->topic('Habitação', proposals: 70, parties: 10, voted: 40, division: 0.3);
        $this->topic('IRS', proposals: 85, parties: 9, voted: 50, division: 0.62);

        $this->get(route('budgets.show', 2027))
            ->assertOk()
            ->assertSee(__('ui.budget.topics.title'))
            ->assertSee(__('ui.budget.topics.tab_parties'))
            ->assertSee(__('ui.budget.topics.tab_proposals'))
            ->assertSee(__('ui.budget.topics.tab_divided'))
            ->assertSee(route('budgets.topics.show', ['year' => 2027, 'topic' => 'habitacao']), false)
            ->assertSee('62% de divisão')
            ->assertSeeInOrder(['id="topics-panel-parties"', 'Habitação', 'IRS', 'id="topics-panel-proposals"', 'IRS', 'Habitação'], false);
    }

    public function test_the_budget_page_has_no_topics_section_before_any_topic_is_found(): void
    {
        $this->get(route('budgets.show', 2027))->assertDontSee(__('ui.budget.topics.title'));
    }

    public function test_the_topic_page_is_the_matrix_of_its_amendments(): void
    {
        $tolls = $this->proposal('1C', 'Fim das portagens', ['PS']);
        $this->proposal('2C', 'Gatos de rua', ['PAN']);
        $topic = $this->topic('Portagens', proposals: 1, parties: 1);
        $topic->proposals()->attach($tolls);

        $this->get(route('budgets.topics.show', ['year' => 2027, 'topic' => 'portagens']))
            ->assertOk()
            ->assertSee('<title>Portagens — OE 2027 — VotoClaro</title>', false)
            ->assertSee(__('ui.budget.topics.eyebrow'))
            ->assertSee('1 proposta de alteração ao Orçamento do Estado para 2027, de 1 partidos')
            ->assertSee('Fim das portagens')
            ->assertDontSee('Gatos de rua')
            ->assertSee('action="'.route('budgets.topics.show', ['year' => 2027, 'topic' => 'portagens']).'"', false);
    }

    public function test_the_topic_page_takes_the_other_filters_too(): void
    {
        $byPs = $this->proposal('1C', 'Portagens do PS', ['PS']);
        $byChega = $this->proposal('2C', 'Portagens do Chega', ['CH']);
        $this->topic('Portagens')->proposals()->attach([$byPs->id, $byChega->id]);

        $this->get(route('budgets.topics.show', ['year' => 2027, 'topic' => 'portagens', 'partido' => 'ch']))
            ->assertSee('Portagens do Chega')
            ->assertDontSee('Portagens do PS');
    }

    public function test_an_unknown_topic_is_not_found(): void
    {
        $this->get(route('budgets.topics.show', ['year' => 2027, 'topic' => 'nada']))->assertNotFound();
    }

    public function test_a_topic_of_another_legislature_is_sent_to_it(): void
    {
        $previous = Legislature::factory()->create(['sigla' => 'XVI', 'number' => 16, 'start_date' => '2024-03-26', 'end_date' => '2025-06-02']);
        $old = Budget::factory()->create(['legislature_id' => $previous->id, 'year' => 2025]);
        BudgetTopic::factory()->create(['budget_id' => $old->id, 'slug' => 'irs']);

        $this->get(route('budgets.topics.show', ['year' => 2025, 'topic' => 'irs']))
            ->assertRedirect(route('legislatures.budgets.topics.show', ['legislature' => 'XVI', 'year' => 2025, 'topic' => 'irs']));
    }

    public function test_the_list_puts_first_the_figure_its_tab_ranks_by(): void
    {
        $topic = $this->topic('IRS', proposals: 85, parties: 9, voted: 50, division: 0.62);

        $this->assertSame(['9 partidos', '85 propostas'], $this->topicList('parties')->figures($topic));
        $this->assertSame(['85 propostas', '9 partidos'], $this->topicList('proposals')->figures($topic));
        $this->assertSame(['62% de divisão', '85 propostas'], $this->topicList('divided')->figures($topic));
    }

    public function test_the_list_draws_the_division_bar_only_in_its_tab(): void
    {
        $this->assertTrue($this->topicList('divided')->showsDivision());
        $this->assertFalse($this->topicList('parties')->showsDivision());
        $this->assertFalse($this->topicList('proposals')->showsDivision());
    }

    public function test_the_list_links_each_topic_under_its_legislature(): void
    {
        $topic = $this->topic('IRS');

        $this->assertSame(route('budgets.topics.show', ['year' => 2027, 'topic' => 'irs']), $this->topicList('parties')->url($topic));
    }

    /**
     * @param  list<string>  $proposers
     * @param  array<string, string>  $positions  the decisive vote, by party
     */
    private function proposal(string $number, string $subject, array $proposers = ['PS'], array $positions = []): BudgetProposal
    {
        $proposal = BudgetProposal::factory()->create([
            'budget_id' => $this->budget->id,
            'number' => $number,
            'slug' => str("{$number} {$subject}")->slug()->toString(),
            'title' => "Artigo 1.º ({$subject})",
        ]);
        $proposal->parties()->attach(Party::query()->whereIn('acronym', $proposers)->pluck('id'));

        if ($positions !== []) {
            $vote = BudgetVote::factory()->create(['budget_proposal_id' => $proposal->id, 'stage' => BudgetVoteStage::Committee, 'is_final' => true]);

            foreach ($positions as $party => $position) {
                $vote->positions()->create(['party' => $party, 'position' => $position]);
            }
        }

        return $proposal;
    }

    private function topic(string $label, int $proposals = 10, int $parties = 3, int $voted = 8, ?float $division = 0.5): BudgetTopic
    {
        return BudgetTopic::factory()->create([
            'budget_id' => $this->budget->id,
            'slug' => str($label)->slug()->toString(),
            'label' => $label,
            'proposals_count' => $proposals,
            'parties_count' => $parties,
            'voted_count' => $voted,
            'division' => $division,
        ]);
    }

    private function topicList(string $rankBy): BudgetTopicList
    {
        return app()->make(BudgetTopicList::class, ['budget' => $this->budget->fresh('legislature'), 'topics' => collect(), 'rankBy' => $rankBy]);
    }
}
