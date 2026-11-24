<?php

namespace Tests\Feature;

use App\Enums\BudgetProposalStatus;
use App\Enums\BudgetVoteStage;
use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\BudgetTheme;
use App\Models\BudgetVote;
use App\Models\Initiative;
use App\Models\Legislature;
use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetPagesTest extends TestCase
{
    use RefreshDatabase;

    private Legislature $previous;

    private Legislature $current;

    private Budget $budget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previous = Legislature::factory()->withParties(['PS', 'PSD', 'BE'])->create([
            'sigla' => 'XVI',
            'number' => 16,
            'start_date' => '2024-03-26',
            'end_date' => '2025-06-02',
        ]);
        $this->current = Legislature::factory()->xvii()->withParties()->create();
        $this->budget = Budget::factory()->create(['legislature_id' => $this->current->id, 'year' => 2027]);
    }

    public function test_the_menu_links_to_the_budget(): void
    {
        $this->get(route('dashboard'))->assertSee(route('budgets.index'));
    }

    public function test_the_list_shows_the_budgets_of_the_legislature(): void
    {
        Budget::factory()->create(['legislature_id' => $this->previous->id, 'year' => 2025]);
        $this->proposal('1C', status: BudgetProposalStatus::Pending);

        $this->get(route('budgets.index'))
            ->assertOk()
            ->assertSee('Orçamento do Estado para 2027')
            ->assertDontSee('Orçamento do Estado para 2025')
            ->assertSee(__('ui.budget.in_progress'))
            ->assertSee(__('ui.budget.open_budget'));

        $this->get(route('legislatures.budgets.index', 'XVI'))
            ->assertOk()
            ->assertSee('Orçamento do Estado para 2025')
            ->assertDontSee('Orçamento do Estado para 2027');
    }

    public function test_the_list_says_when_there_is_no_budget_yet(): void
    {
        $this->get(route('legislatures.budgets.index', 'XVI'))->assertOk()->assertSee(__('ui.budget.empty'));
    }

    public function test_the_budget_page_sums_up_the_amendments(): void
    {
        $this->proposal('1C', ['PSD'], BudgetProposalStatus::Approved, ['PSD' => 'favor', 'PS' => 'contra']);
        $this->proposal('2C', ['PS'], BudgetProposalStatus::Rejected, ['PSD' => 'contra', 'PS' => 'favor']);
        $this->proposal('3C', ['PS'], BudgetProposalStatus::PartiallyApproved, ['PSD' => 'abstencao', 'PS' => 'favor']);
        $this->proposal('4C', ['BE'], BudgetProposalStatus::Pending);

        $this->get(route('budgets.show', 2027))
            ->assertOk()
            ->assertViewHas('overview', ['total' => 4, 'approved' => 2, 'rejected' => 1, 'pending' => 1, 'voted' => 3, 'against_government' => null])
            ->assertViewHas('parties', function (array $parties): bool {
                $byParty = collect($parties)->keyBy('acronym');

                return $byParty['PSD']['proposed'] === 1
                    && $byParty['PSD']['approved'] === 1
                    && $byParty['PSD']['others_voted'] === 2
                    && $byParty['PSD']['against_others'] === 1
                    && $byParty['PSD']['against_rate'] === 50.0
                    && $byParty['PS']['proposed'] === 2
                    && $byParty['PS']['approved'] === 1
                    && $byParty['PS']['approval_rate'] === 50.0
                    && $byParty['PS']['against_others'] === 1;
            })
            ->assertSee(__('ui.budget.voting_progress', ['voted' => 3, 'total' => 4]));
    }

    public function test_the_budget_page_follows_the_order_the_parliament_lists_the_parties_in(): void
    {
        $this->budget->update(['party_order' => ['PSD', 'CH', 'PS']]);

        $this->get(route('budgets.show', 2027))
            ->assertViewHas('parties', fn (array $parties): bool => array_column($parties, 'acronym') === ['PSD', 'CH', 'PS']);
    }

    public function test_the_budget_page_links_to_its_bill_and_to_each_theme(): void
    {
        $bill = Initiative::factory()->create(['legislature_id' => $this->current->id, 'title' => 'Orçamento do Estado para 2027']);
        $this->budget->update(['initiative_id' => $bill->id]);
        $this->proposal('1C', themes: ['Saúde']);

        $this->get(route('budgets.show', 2027))
            ->assertSee(route('initiatives.show', $bill))
            ->assertSee(route('budgets.proposals.index', ['year' => 2027, 'tema' => 'saude']), false)
            ->assertSee('Saúde');
    }

    public function test_a_budget_of_another_legislature_is_sent_to_it(): void
    {
        Budget::factory()->create(['legislature_id' => $this->previous->id, 'year' => 2025]);

        $this->get('/orcamento/2025/propostas?tema=saude')
            ->assertStatus(301)
            ->assertRedirect('/legislatura/XVI/orcamento/2025/propostas?tema=saude');
    }

    public function test_the_current_legislature_is_served_without_the_prefix(): void
    {
        $this->get('/legislatura/XVII/orcamento/2027')->assertStatus(301)->assertRedirect(route('budgets.show', 2027));
    }

    public function test_an_unknown_budget_is_not_found(): void
    {
        $this->get('/orcamento/2030')->assertNotFound();
        $this->get('/orcamento/2030/propostas')->assertNotFound();
    }

    public function test_the_matrix_shows_how_each_party_voted_each_amendment(): void
    {
        $this->proposal('1907C', ['PS'], BudgetProposalStatus::Rejected, ['PSD' => 'contra', 'CH' => 'abstencao', 'PS' => 'favor'], title: 'Artigo 119.º-A (Saúde e direitos das mulheres na menopausa)');

        $this->get(route('budgets.proposals.index', 2027))
            ->assertOk()
            ->assertSee('Saúde e direitos das mulheres na menopausa')
            ->assertSee('Artigo 119.º-A')
            ->assertSee('PSD: Contra')
            ->assertSee('CH: Abstenção')
            ->assertSee('PS: A favor')
            ->assertViewHas('parties', ['PSD', 'CH', 'PS']);
    }

    public function test_the_matrix_filters_by_theme(): void
    {
        $this->proposal('1C', title: 'Proposta da saúde', themes: ['Saúde']);
        $this->proposal('2C', title: 'Proposta da defesa', themes: ['Defesa Nacional']);

        $this->get(route('budgets.proposals.index', ['year' => 2027, 'tema' => 'saude']))
            ->assertSee('Proposta da saúde')
            ->assertDontSee('Proposta da defesa');
    }

    public function test_the_matrix_of_a_theme_is_titled_by_it(): void
    {
        $this->proposal('1C', themes: ['Infraestruturas, Mobilidade e Habitação']);
        $this->proposal('2C', themes: ['Infraestruturas, Mobilidade e Habitação']);

        $this->get(route('budgets.proposals.index', ['year' => 2027, 'tema' => 'infraestruturas-mobilidade-e-habitacao']))
            ->assertOk()
            ->assertSee('<title>Infraestruturas, Mobilidade e Habitação — OE 2027 — VotoClaro</title>', false)
            ->assertSee('2 propostas de alteração ao Orçamento do Estado para 2027')
            ->assertSeeInOrder([__('ui.budget.matrix.title'), 'Infraestruturas, Mobilidade e Habitação', '<h1', 'Infraestruturas, Mobilidade e Habitação'], false);
    }

    public function test_the_matrix_without_a_theme_keeps_its_own_title(): void
    {
        $this->proposal('1C', themes: ['Saúde']);

        $this->get(route('budgets.proposals.index', 2027))
            ->assertSee('<title>'.__('ui.budget.matrix.title').' — OE 2027 — VotoClaro</title>', false)
            ->assertDontSee(__('ui.budget.matrix.theme_subtitle'));
    }

    public function test_the_matrix_filters_by_proposing_party(): void
    {
        $this->proposal('1C', ['BE'], title: 'Proposta do BE');
        $this->proposal('2C', ['PS'], title: 'Proposta do PS');

        $this->get(route('budgets.proposals.index', ['year' => 2027, 'partido' => 'be']))
            ->assertSee('Proposta do BE')
            ->assertDontSee('Proposta do PS');
    }

    public function test_the_matrix_filters_by_status(): void
    {
        $this->proposal('1C', status: BudgetProposalStatus::Approved, title: 'Proposta aprovada');
        $this->proposal('2C', status: BudgetProposalStatus::Rejected, title: 'Proposta rejeitada');

        $this->get(route('budgets.proposals.index', ['year' => 2027, 'estado' => 'approved']))
            ->assertSee('Proposta aprovada')
            ->assertDontSee('Proposta rejeitada');
    }

    public function test_the_matrix_searches_the_titles(): void
    {
        $this->proposal('1C', title: 'Estratégia para a menopausa');
        $this->proposal('2C', title: 'Ferrovia no interior');

        $this->get(route('budgets.proposals.index', ['year' => 2027, 'q' => 'menopausa']))
            ->assertSee('Estratégia para a menopausa')
            ->assertDontSee('Ferrovia no interior')
            ->assertSee('1 proposta');
    }

    public function test_the_matrix_rejects_an_unknown_status(): void
    {
        $this->get(route('budgets.proposals.index', ['year' => 2027, 'estado' => 'nada']))->assertSessionHasErrors('estado');
    }

    public function test_the_matrix_says_when_nothing_matches(): void
    {
        $this->get(route('budgets.proposals.index', ['year' => 2027, 'q' => 'nada']))->assertSee(__('ui.budget.matrix.no_results'));
    }

    public function test_the_matrix_counts_every_matching_amendment_not_only_the_page(): void
    {
        foreach (range(1, 51) as $number) {
            $this->proposal("{$number}C", positions: ['PSD' => 'contra']);
        }

        $this->get(route('budgets.proposals.index', 2027))
            ->assertViewHas('proposals', fn ($proposals): bool => $proposals->count() === 50 && $proposals->total() === 51)
            ->assertViewHas('totals', fn (array $totals): bool => $totals['PSD']['contra'] === 51);
    }

    public function test_the_matrix_pages_are_navigated_in_portuguese(): void
    {
        foreach (range(1, 51) as $number) {
            $this->proposal("{$number}C");
        }

        $this->get(route('budgets.proposals.index', 2027))
            ->assertSee('A mostrar')
            ->assertSee('resultados')
            ->assertDontSee('Showing');
    }

    public function test_the_matrix_lists_the_amendments_by_number(): void
    {
        $this->proposal('100C', title: 'Proposta cem');
        $this->proposal('20C', title: 'Proposta vinte');
        $this->proposal('3C', title: 'Proposta três');

        $this->get(route('budgets.proposals.index', 2027))->assertSeeInOrder(['Proposta três', 'Proposta vinte', 'Proposta cem']);
    }

    public function test_the_proposal_page_shows_each_vote_with_the_decisive_one_first(): void
    {
        $proposal = $this->proposal('2770C', ['CH'], BudgetProposalStatus::Approved, title: 'Artigo 34.º-A (Transmissão de espetáculos tauromáquicos)');
        $this->vote($proposal, 1, BudgetVoteStage::Plenary, ['PSD' => 'favor', 'JPP' => 'contra'], final: true, date: '2026-11-19');
        $this->vote($proposal, 2, BudgetVoteStage::Committee, ['PSD' => 'favor', 'PS' => 'abstencao'], final: false, date: '2026-11-18');

        $this->get(route('budgets.proposals.show', ['year' => 2027, 'proposal' => $proposal->slug]))
            ->assertOk()
            ->assertSee('Transmissão de espetáculos tauromáquicos')
            ->assertSee('Artigo 34.º-A')
            ->assertSeeInOrder([__('ui.budget.stage.plenary'), '19/11/2026', __('ui.budget.proposal.vote_final'), __('ui.budget.stage.committee'), '18/11/2026'])
            ->assertSee('JPP: Contra');
    }

    public function test_the_proposal_page_names_a_deputy_who_voted_apart(): void
    {
        $proposal = $this->proposal('1740C', ['PSD']);
        $vote = $this->vote($proposal, 1, BudgetVoteStage::Committee, ['PSD' => 'contra']);
        $vote->positions()->create(['party' => 'PSD', 'deputy' => 'Pedro Coelho', 'position' => 'favor']);

        $this->get(route('budgets.proposals.show', ['year' => 2027, 'proposal' => $proposal->slug]))
            ->assertSee(__('ui.budget.proposal.deputies'))
            ->assertSee('Pedro Coelho (PSD)');
    }

    public function test_the_proposal_page_says_when_it_was_not_voted_yet(): void
    {
        $proposal = $this->proposal('726C', status: BudgetProposalStatus::Pending);

        $this->get(route('budgets.proposals.show', ['year' => 2027, 'proposal' => $proposal->slug]))->assertSee(__('ui.budget.proposal.no_votes'));
    }

    public function test_a_proposal_named_by_its_number_or_an_old_slug_is_sent_to_its_address(): void
    {
        $proposal = $this->proposal('1907C', title: 'Artigo 119.º-A (Saúde e direitos das mulheres na menopausa)');
        $this->assertSame('1907c-saude-e-direitos-das-mulheres-na-menopausa', $proposal->slug);
        $url = route('budgets.proposals.show', ['year' => 2027, 'proposal' => $proposal->slug]);

        $this->get('/orcamento/2027/propostas/1907C')->assertStatus(301)->assertRedirect($url);
        $this->get('/orcamento/2027/propostas/1907c-titulo-antigo')->assertStatus(301)->assertRedirect($url);
    }

    public function test_a_proposal_with_a_hyphen_in_its_number_is_found_by_it(): void
    {
        $this->proposal('2742C', title: 'Artigo 1.º (Outra)');
        $proposal = $this->proposal('2742C-1', title: 'Artigo 2.º (Imóveis rústicos)');

        $this->get('/orcamento/2027/propostas/2742c-1')
            ->assertStatus(301)
            ->assertRedirect(route('budgets.proposals.show', ['year' => 2027, 'proposal' => $proposal->slug]));
    }

    public function test_an_unknown_proposal_is_not_found(): void
    {
        $this->get('/orcamento/2027/propostas/9999c')->assertNotFound();
    }

    public function test_the_switcher_leads_from_a_budget_to_the_budget_list_of_the_other_legislature(): void
    {
        $this->get(route('budgets.show', 2027))
            ->assertSee('value="'.route('legislatures.budgets.index', 'XVI').'"', false)
            ->assertSee('value="'.route('budgets.show', 2027).'" selected', false);
    }

    /**
     * @param  list<string>  $proposers
     * @param  array<string, string>  $positions  the decisive committee vote, by party
     * @param  list<string>  $themes
     */
    private function proposal(
        string $number,
        array $proposers = ['PS'],
        BudgetProposalStatus $status = BudgetProposalStatus::Rejected,
        array $positions = [],
        ?string $title = null,
        array $themes = [],
    ): BudgetProposal {
        $title ??= "Artigo 1.º (Proposta {$number})";
        $proposal = BudgetProposal::factory()->create([
            'budget_id' => $this->budget->id,
            'number' => $number,
            'slug' => str($number.' '.(preg_match('/\((.+)\)$/u', $title, $m) ? $m[1] : $title))->slug()->toString(),
            'title' => $title,
            'status' => $status,
        ]);

        $proposal->parties()->attach(Party::query()->whereIn('acronym', $proposers)->pluck('id'));

        foreach ($themes as $theme) {
            $proposal->themes()->attach(BudgetTheme::firstOrCreate(['name' => $theme], ['slug' => str($theme)->slug()->toString()]));
        }

        if ($positions !== []) {
            $this->vote($proposal, 1, BudgetVoteStage::Committee, $positions)->update(['result' => $status]);
        }

        return $proposal;
    }

    public function test_the_budget_page_counts_the_amendments_passed_against_the_government(): void
    {
        $this->markGovernment(['PSD', 'CDS-PP']);
        $this->againstGovernmentProposals();

        $this->get(route('budgets.show', 2027))
            ->assertOk()
            ->assertViewHas('overview', fn (array $overview): bool => $overview['against_government'] === 2)
            ->assertViewHas('parties', function (array $parties): bool {
                $byParty = collect($parties)->keyBy('acronym');

                return $byParty['PS']['approved_against_government'] === 1
                    && $byParty['CH']['approved_against_government'] === 1
                    && $byParty['BE']['approved_against_government'] === 0;
            })
            ->assertSee(__('ui.budget.against_government.label'))
            ->assertSee(__('ui.budget.against_government.explain', ['parties' => 'PSD, CDS-PP']))
            ->assertSee(route('budgets.proposals.index', ['year' => 2027, 'contra_governo' => 1]), false);
    }

    public function test_without_a_government_on_record_nothing_is_counted_against_it(): void
    {
        $this->againstGovernmentProposals();

        $this->get(route('budgets.show', 2027))
            ->assertOk()
            ->assertViewHas('overview', fn (array $overview): bool => $overview['against_government'] === null)
            ->assertDontSee(__('ui.budget.against_government.label'));

        $this->get(route('budgets.proposals.index', ['year' => 2027, 'contra_governo' => 1]))
            ->assertOk()
            ->assertDontSee('name="contra_governo"', false)
            ->assertViewHas('proposals', fn ($proposals): bool => $proposals->total() === 5);
    }

    public function test_the_matrix_filters_the_amendments_passed_against_the_government(): void
    {
        $this->markGovernment(['PSD', 'CDS-PP']);
        $this->againstGovernmentProposals();

        $this->get(route('budgets.proposals.index', ['year' => 2027, 'contra_governo' => 1]))
            ->assertOk()
            ->assertSee('name="contra_governo"', false)
            ->assertViewHas('proposals', fn ($proposals): bool => $proposals->pluck('number')->sort()->values()->all() === ['1C', '5C']);

        $this->get(route('budgets.proposals.index', ['year' => 2027, 'contra_governo' => 1, 'partido' => 'ps']))
            ->assertViewHas('proposals', fn ($proposals): bool => $proposals->pluck('number')->all() === ['1C']);
    }

    public function test_a_single_party_government_counts_on_its_own_vote(): void
    {
        $this->markGovernment(['PS']);
        $this->proposal('1C', ['PSD'], BudgetProposalStatus::Approved, ['PS' => 'contra', 'PSD' => 'favor']);
        $this->proposal('2C', ['PSD'], BudgetProposalStatus::Approved, ['PS' => 'abstencao', 'PSD' => 'favor']);

        $this->get(route('budgets.show', 2027))
            ->assertViewHas('overview', fn (array $overview): bool => $overview['against_government'] === 1);
    }

    public function test_the_home_page_hero_links_to_the_amendments_passed_against_the_government(): void
    {
        $this->markGovernment(['PSD', 'CDS-PP']);
        $this->againstGovernmentProposals();
        $this->proposal('9C', ['BE'], BudgetProposalStatus::Pending);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(trans_choice('ui.home.against_government', 2, ['count' => 2, 'parties' => 'PSD, CDS-PP']))
            ->assertSee(route('budgets.proposals.index', ['year' => 2027, 'contra_governo' => 1]), false);
    }

    public function test_the_budget_page_shows_how_often_each_pair_of_parties_voted_alike(): void
    {
        $this->agreementProposals();

        $this->get(route('budgets.show', 2027))
            ->assertOk()
            ->assertSee(__('ui.budget.agreement.title'))
            ->assertViewHas('agreement', function (array $agreement): bool {
                $pairs = $agreement['pairs'];

                return $agreement['parties'] === ['PSD', 'CH', 'PS', 'CDS-PP']
                    && $pairs['PSD']['CDS-PP'] === ['voted' => 23, 'same' => 23, 'rate' => 100.0]
                    && $pairs['PS']['CH'] === ['voted' => 21, 'same' => 11, 'rate' => 52.4]
                    && $pairs['CH']['PS'] === $pairs['PS']['CH']
                    && $pairs['PSD']['PS'] === ['voted' => 22, 'same' => 0, 'rate' => 0.0];
            })
            ->assertSee(route('budgets.proposals.index', ['year' => 2027, 'divergem' => 'ps,ch']), false)
            ->assertSee('PS e CH votaram igual em 11 das 21 propostas que ambos votaram');
    }

    public function test_absences_and_split_votes_are_left_out_of_the_agreement(): void
    {
        $this->agreementProposals();

        $this->get(route('budgets.show', 2027))
            ->assertViewHas('agreement', fn (array $agreement): bool => $agreement['pairs']['PSD']['CH']['voted'] === 21
                && $agreement['pairs']['PSD']['PS']['voted'] === 22);
    }

    public function test_a_pair_that_shared_few_votes_has_no_agreement_rate(): void
    {
        foreach (range(1, 19) as $number) {
            $this->proposal("{$number}C", positions: ['PS' => 'favor', 'BE' => 'favor']);
        }

        $this->get(route('budgets.show', 2027))
            ->assertViewHas('agreement', fn (array $agreement): bool => $agreement['pairs']['PS']['BE'] === ['voted' => 19, 'same' => 19, 'rate' => null]);
    }

    public function test_the_matrix_filters_the_amendments_two_parties_voted_apart(): void
    {
        $this->agreementProposals();

        $this->get(route('budgets.proposals.index', ['year' => 2027, 'divergem' => 'ps,ch']))
            ->assertOk()
            ->assertSee(__('ui.budget.matrix.voted_apart', ['first' => 'PS', 'second' => 'CH']))
            ->assertSee('name="divergem" value="ps,ch"', false)
            ->assertViewHas('proposals', fn ($proposals): bool => $proposals->total() === 10 && ! $proposals->pluck('number')->contains('A3C'));

        $this->get(route('budgets.proposals.index', ['year' => 2027, 'divergem' => 'psd,ch']))
            ->assertViewHas('proposals', fn ($proposals): bool => $proposals->total() === 11
                && $proposals->pluck('number')->contains('A3C')
                && ! $proposals->pluck('number')->contains('A1C')
                && ! $proposals->pluck('number')->contains('A2C'));
    }

    public function test_the_voted_apart_filter_is_ignored_unless_it_names_two_parties_that_voted(): void
    {
        $this->agreementProposals();
        $total = BudgetProposal::query()->count();

        foreach (['ps', 'ps,ps', 'ps,xyz', 'ps,ch,psd', ','] as $named) {
            $this->get(route('budgets.proposals.index', ['year' => 2027, 'divergem' => $named]))
                ->assertOk()
                ->assertDontSee('name="divergem"', false)
                ->assertViewHas('proposals', fn ($proposals): bool => $proposals->total() === $total);
        }
    }

    /**
     * Twenty amendments the PSD and the CDS-PP voted for and the PS against, the CH voting with
     * the PS on half of them; then one the CH missed (A1C), one the CH voted in parts apart
     * (A2C), and one where the CH abstained against the PSD's vote (A3C), the PS with the CH.
     */
    private function agreementProposals(): void
    {
        foreach (range(1, 20) as $number) {
            $this->proposal("{$number}C", positions: ['PSD' => 'favor', 'CDS-PP' => 'favor', 'PS' => 'contra', 'CH' => $number <= 10 ? 'contra' : 'favor']);
        }

        $this->proposal('A1C', positions: ['PSD' => 'favor', 'CDS-PP' => 'favor', 'PS' => 'contra', 'CH' => 'ausente']);

        $split = $this->proposal('A2C', status: BudgetProposalStatus::PartiallyApproved);
        $this->vote($split, 1, BudgetVoteStage::Committee, ['PSD' => 'favor', 'CDS-PP' => 'favor', 'CH' => 'favor']);
        $this->vote($split, 2, BudgetVoteStage::Committee, ['PSD' => 'favor', 'CDS-PP' => 'favor', 'CH' => 'contra']);

        $this->proposal('A3C', positions: ['PSD' => 'favor', 'CDS-PP' => 'favor', 'PS' => 'abstencao', 'CH' => 'abstencao']);
    }

    /**
     * @param  list<string>  $acronyms
     */
    private function markGovernment(array $acronyms): void
    {
        $this->current->parties()->updateExistingPivot(Party::query()->whereIn('acronym', $acronyms)->pluck('id')->all(), ['is_government' => true]);
    }

    /**
     * Two amendments passed against the whole government (1C, and 5C in one of its parts); the
     * others either had a government party on their side, failed, or passed only in a part the
     * government supported.
     */
    private function againstGovernmentProposals(): void
    {
        $this->proposal('1C', ['PS'], BudgetProposalStatus::Approved, ['PSD' => 'contra', 'CDS-PP' => 'contra', 'PS' => 'favor', 'CH' => 'favor']);
        $this->proposal('2C', ['BE'], BudgetProposalStatus::Approved, ['PSD' => 'contra', 'CDS-PP' => 'favor', 'PS' => 'favor']);
        $this->proposal('3C', ['BE'], BudgetProposalStatus::Rejected, ['PSD' => 'contra', 'CDS-PP' => 'contra', 'PS' => 'favor']);

        $split = $this->proposal('4C', ['BE'], BudgetProposalStatus::PartiallyApproved);
        $this->vote($split, 1, BudgetVoteStage::Committee, ['PSD' => 'favor', 'CDS-PP' => 'favor'])->update(['result' => BudgetProposalStatus::Approved]);
        $this->vote($split, 2, BudgetVoteStage::Committee, ['PSD' => 'contra', 'CDS-PP' => 'contra'])->update(['result' => BudgetProposalStatus::Rejected]);

        $passedInPart = $this->proposal('5C', ['CH'], BudgetProposalStatus::PartiallyApproved);
        $this->vote($passedInPart, 1, BudgetVoteStage::Committee, ['PSD' => 'contra', 'CDS-PP' => 'contra', 'CH' => 'favor', 'PS' => 'favor'])->update(['result' => BudgetProposalStatus::Approved]);
        $this->vote($passedInPart, 2, BudgetVoteStage::Committee, ['PSD' => 'contra', 'CDS-PP' => 'contra', 'CH' => 'favor'])->update(['result' => BudgetProposalStatus::Rejected]);
    }

    /**
     * @param  array<string, string>  $positions
     */
    private function vote(BudgetProposal $proposal, int $sequence, BudgetVoteStage $stage, array $positions, bool $final = true, string $date = '2026-11-20'): BudgetVote
    {
        $vote = BudgetVote::factory()->create([
            'budget_proposal_id' => $proposal->id,
            'sequence' => $sequence,
            'stage' => $stage,
            'date' => $date,
            'is_final' => $final,
        ]);

        foreach ($positions as $party => $position) {
            $vote->positions()->create(['party' => $party, 'position' => $position]);
        }

        return $vote;
    }
}
