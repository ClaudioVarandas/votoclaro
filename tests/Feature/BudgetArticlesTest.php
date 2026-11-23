<?php

namespace Tests\Feature;

use App\Enums\BudgetProposalStatus;
use App\Enums\BudgetVoteStage;
use App\Models\Budget;
use App\Models\BudgetArticle;
use App\Models\BudgetProposal;
use App\Models\BudgetVote;
use App\Models\Legislature;
use App\Services\BudgetArticleSyncService;
use App\Services\BudgetStatsService;
use App\View\Components\BudgetVoteMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BudgetArticlesTest extends TestCase
{
    use RefreshDatabase;

    private const BILL_URL = 'https://parlamento.test/OE2026Or_json.txt';

    private Legislature $current;

    private Budget $budget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->current = Legislature::factory()->xvii()->withParties()->create();
        $this->budget = Budget::factory()->create([
            'legislature_id' => $this->current->id,
            'year' => 2026,
            'budget_url' => self::BILL_URL,
            'party_order' => ['PSD', 'CH', 'PS', 'IL', 'L', 'PCP', 'CDS-PP', 'BE', 'PAN'],
        ]);
    }

    public function test_sync_imports_only_the_articles_of_the_bill_in_its_order(): void
    {
        $imported = $this->syncFixture();

        $this->assertSame(6, $imported);
        $this->assertSame(
            ['Artigo 1.º', 'Artigo 60.º', 'Artigo 25.º', 'Artigo 38.º-A', 'Artigo 38.º-A', 'Artigo 10.º-A'],
            $this->budget->articles()->pluck('number')->all(),
        );
    }

    public function test_sync_keeps_the_law_an_article_changes_and_a_clean_address(): void
    {
        $this->syncFixture();

        $irs = BudgetArticle::query()->findOrFail(74839);
        $this->assertSame('artigo-60', $irs->number_slug);
        $this->assertStringStartsWith('artigo-60-alteracao-ao-codigo-do-imposto-sobre-o-rendimento', $irs->slug);
        $this->assertSame('Decreto-Lei n.º 442-A/88, de 30 de novembro', $irs->law_title);
        $this->assertStringContainsString('(IRS)', $irs->law_subtitle);
        $this->assertTrue($irs->amendsLaw());
        $this->assertSame('artigo-38-a', BudgetArticle::query()->findOrFail(78337)->number_slug);
        $this->assertFalse(BudgetArticle::query()->findOrFail(74309)->amendsLaw());
    }

    public function test_sync_reads_the_text_with_plain_line_breaks_and_the_status(): void
    {
        $this->syncFixture();

        $first = BudgetArticle::query()->findOrFail(74309);
        $this->assertStringNotContainsString("\r", $first->text);
        $this->assertSame(BudgetProposalStatus::Approved, $first->status);
        $this->assertSame('Aprovado(a) em Comissão', $first->status_label);
    }

    public function test_sync_records_every_vote_with_the_decisive_ones(): void
    {
        $this->syncFixture();

        $first = BudgetArticle::query()->findOrFail(74309);
        $this->assertSame(3, $first->votes()->count());
        $this->assertSame(3, $first->finalVotes()->count());
        $this->assertSame('favor', $first->votes()->first()->positions()->where('party', 'PSD')->value('position')->value);
        $this->assertStringStartsWith('Mapa 1 — ', $first->votes()->where('sequence', 2)->value('subject'));

        $plenary = BudgetArticle::query()->findOrFail(74186);
        $this->assertSame(BudgetVoteStage::Plenary, $plenary->finalVotes()->sole()->stage);
        $this->assertSame(0, BudgetArticle::query()->findOrFail(76142)->votes()->count());
    }

    public function test_sync_links_the_amendments_already_imported_and_skips_the_rest(): void
    {
        $known = BudgetProposal::factory()->create(['id' => 25329, 'budget_id' => $this->budget->id]);
        BudgetProposal::factory()->create(['id' => 24266, 'budget_id' => $this->budget->id]);

        $this->syncFixture();

        $this->assertSame([$known->id], BudgetArticle::query()->findOrFail(74839)->proposals()->pluck('budget_proposals.id')->all());
        $this->assertSame(['Artigo 60.º'], $known->articles()->pluck('number')->all());
        $this->assertSame(2, \DB::table('budget_article_budget_proposal')->count());
    }

    public function test_articles_that_share_a_number_and_title_get_distinct_addresses(): void
    {
        $twin = ['Tipo' => 'Iniciativas/Artigos', 'Numero' => 'Artigo 22.º-A', 'Titulo' => 'Psicólogos nas escolas', 'Estado' => 'Aprovado(a) em Comissão'];
        $this->fakeBill(['Item' => [['ID' => '90001', ...$twin], ['ID' => '90002', ...$twin]]]);

        app(BudgetArticleSyncService::class)->sync($this->budget);

        $this->assertSame(
            ['artigo-22-a-psicologos-nas-escolas', 'artigo-22-a-psicologos-nas-escolas-90002'],
            $this->budget->articles()->pluck('slug')->all(),
        );
    }

    public function test_an_article_the_bill_no_longer_has_is_removed(): void
    {
        $gone = BudgetArticle::factory()->create(['budget_id' => $this->budget->id]);

        $this->syncFixture();

        $this->assertModelMissing($gone);
    }

    public function test_sync_needs_the_bill_url(): void
    {
        $this->budget->update(['budget_url' => null]);

        $this->expectExceptionMessage('The 2026 budget has no bill URL.');

        app(BudgetArticleSyncService::class)->sync($this->budget);
    }

    public function test_import_brings_the_articles_after_the_amendments(): void
    {
        Http::fake([
            $this->budget->proposals_url => Http::response(['PropostaDeAlteracao' => []]),
            self::BILL_URL => Http::response($this->fixture()),
        ]);

        $this->artisan('ar:budget:import', ['--year' => 2026])
            ->expectsOutputToContain("Downloading the Government's bill for 2026...")
            ->expectsOutputToContain('Sync completed: 6 articles imported.')
            ->assertSuccessful();
    }

    public function test_the_stats_count_the_articles_and_how_each_party_voted_them(): void
    {
        $voted = $this->article('Artigo 1.º', 'Objeto', BudgetProposalStatus::Approved, ['PSD' => 'favor', 'PS' => 'abstencao', 'CH' => 'contra']);
        $this->article('Artigo 2.º', 'Valor reforçado', BudgetProposalStatus::Approved, ['PSD' => 'favor', 'PS' => 'abstencao', 'CH' => 'favor']);
        $this->article('Artigo 3.º', 'Por votar');
        $voted->proposals()->attach(BudgetProposal::factory()->count(2)->create(['budget_id' => $this->budget->id]));

        $stats = app(BudgetStatsService::class);
        $articles = $stats->articles($this->budget);
        $parties = collect($stats->parties($this->budget))->keyBy('acronym');

        $this->assertSame([3, 2, 1, 2], [$articles['total'], $articles['approved'], $articles['pending'], $articles['voted']]);
        $this->assertSame(['Artigo 1.º'], $articles['most_amended']->pluck('number')->all());
        $this->assertSame(['favor' => 2, 'contra' => 0, 'abstencao' => 0], $parties['PSD']['articles']);
        $this->assertSame(100.0, $parties['PSD']['articles_favor_rate']);
        $this->assertSame(['favor' => 0, 'contra' => 0, 'abstencao' => 2], $parties['PS']['articles']);
        $this->assertSame(['favor' => 1, 'contra' => 1, 'abstencao' => 0], $parties['CH']['articles']);
    }

    public function test_the_budget_page_sums_up_the_bill_and_how_each_party_voted_it(): void
    {
        $article = $this->article('Artigo 60.º', 'Alteração ao Código do IRS', BudgetProposalStatus::Approved, ['PSD' => 'favor', 'PS' => 'abstencao']);
        $article->proposals()->attach(BudgetProposal::factory()->create(['budget_id' => $this->budget->id]));

        $this->get(route('budgets.show', 2026))
            ->assertOk()
            ->assertSee(__('ui.budget.articles.section_title'))
            ->assertSee(__('ui.budget.articles.most_amended'))
            ->assertSee(route('budgets.articles.show', ['year' => 2026, 'article' => $article->slug]), false)
            ->assertSee(__('ui.budget.articles.party_column'))
            ->assertSee('100% a favor')
            ->assertSee('A favor: 0 · Contra: 0 · Abstenção: 1');
    }

    public function test_the_budget_page_leaves_the_bill_out_before_it_is_imported(): void
    {
        $this->get(route('budgets.show', 2026))
            ->assertDontSee(__('ui.budget.articles.section_title'))
            ->assertDontSee(__('ui.budget.articles.party_column'));
    }

    public function test_the_article_matrix_shows_how_each_party_voted_each_article(): void
    {
        $this->article('Artigo 1.º', 'Objeto', BudgetProposalStatus::Approved, ['PSD' => 'favor', 'CH' => 'contra']);

        $this->get(route('budgets.articles.index', 2026))
            ->assertOk()
            ->assertSee('Objeto')
            ->assertSee('PSD: A favor')
            ->assertSee('CH: Contra')
            ->assertSee('Aprovado')
            ->assertSee('1 artigo, em todas as páginas')
            ->assertDontSee(__('ui.budget.articles.not_voted_yet'));
    }

    public function test_the_article_matrix_says_when_nothing_was_voted_yet(): void
    {
        $this->article('Artigo 1.º', 'Objeto');

        $this->get(route('budgets.articles.index', 2026))->assertSee(__('ui.budget.articles.not_voted_yet'));
    }

    public function test_the_article_matrix_filters_by_status_law_and_search(): void
    {
        $this->article('Artigo 1.º', 'Objeto', BudgetProposalStatus::Approved);
        $this->article('Artigo 60.º', 'Alteração ao Código do IRS', law: 'Aprova o Código do IRS');
        $this->article('Artigo 70.º', 'Contribuição extraordinária');

        $this->get(route('budgets.articles.index', ['year' => 2026, 'lei' => 1]))
            ->assertSee('Alteração ao Código do IRS')
            ->assertDontSee('Contribuição extraordinária');

        $this->get(route('budgets.articles.index', ['year' => 2026, 'estado' => 'approved']))
            ->assertSee('Objeto')
            ->assertDontSee('Contribuição extraordinária');

        $this->get(route('budgets.articles.index', ['year' => 2026, 'q' => 'contribuição']))
            ->assertSee('Contribuição extraordinária')
            ->assertDontSee('Alteração ao Código do IRS');
    }

    public function test_the_article_matrix_rejects_an_unknown_status(): void
    {
        $this->get(route('budgets.articles.index', ['year' => 2026, 'estado' => 'talvez']))->assertSessionHasErrors('estado');
    }

    public function test_the_article_page_shows_the_law_its_votes_and_its_amendments(): void
    {
        $article = $this->article('Artigo 60.º', 'Alteração ao Código do IRS', BudgetProposalStatus::Approved, ['PSD' => 'favor', 'PS' => 'abstencao'], 'Aprova o Código do IRS');
        $article->proposals()->attach(BudgetProposal::factory()->create(['budget_id' => $this->budget->id, 'number' => '12C', 'title' => 'Artigo 60.º (Dedução das rendas)']));

        $this->get(route('budgets.articles.show', ['year' => 2026, 'article' => $article->slug]))
            ->assertOk()
            ->assertSee('Artigo 60.º da proposta de lei')
            ->assertSee('Aprova o Código do IRS')
            ->assertSee('PSD ✓')
            ->assertSee('1 proposta de alteração.')
            ->assertSee('Dedução das rendas')
            ->assertSee('Aprovado')
            ->assertDontSee(__('ui.budget.proposal.vote_final'), 'Every vote was decisive, so none is singled out.');
    }

    public function test_an_article_is_found_by_its_number_or_its_id(): void
    {
        $irs = $this->article('Artigo 60.º', 'Alteração ao Código do IRS');

        $this->get(route('budgets.articles.show', ['year' => 2026, 'article' => 'artigo-60']))
            ->assertRedirect(route('budgets.articles.show', ['year' => 2026, 'article' => $irs->slug]));
        $this->get(route('budgets.articles.show', ['year' => 2026, 'article' => "titulo-antigo-{$irs->id}"]))
            ->assertRedirect(route('budgets.articles.show', ['year' => 2026, 'article' => $irs->slug]));
    }

    public function test_a_number_several_articles_share_finds_none(): void
    {
        $this->article('Artigo 22.º-A', 'Psicólogos');
        $this->article('Artigo 22.º-A', 'Equipas de saúde');
        $this->article('Artigo 22.º', 'Outro');

        $this->get(route('budgets.articles.show', ['year' => 2026, 'article' => 'artigo-22-a']))->assertNotFound();
    }

    public function test_an_article_of_another_legislature_is_sent_to_it(): void
    {
        $previous = Legislature::factory()->create(['sigla' => 'XVI', 'number' => 16, 'start_date' => '2024-03-26', 'end_date' => '2025-06-02']);
        $old = Budget::factory()->create(['legislature_id' => $previous->id, 'year' => 2025]);
        $article = BudgetArticle::factory()->create(['budget_id' => $old->id]);

        $this->get(route('budgets.articles.show', ['year' => 2025, 'article' => $article->slug]))
            ->assertRedirect(route('legislatures.budgets.articles.show', ['legislature' => 'XVI', 'year' => 2025, 'article' => $article->slug]));
    }

    public function test_the_proposal_page_links_to_the_article_it_changes(): void
    {
        $article = $this->article('Artigo 60.º', 'Alteração ao Código do IRS');
        $proposal = BudgetProposal::factory()->create(['budget_id' => $this->budget->id]);
        $article->proposals()->attach($proposal);

        $this->get(route('budgets.proposals.show', ['year' => 2026, 'proposal' => $proposal->slug]))
            ->assertSee(__('ui.budget.proposal.amends_article'))
            ->assertSee(route('budgets.articles.show', ['year' => 2026, 'article' => $article->slug]), false);
    }

    public function test_the_sitemap_lists_the_articles(): void
    {
        $article = $this->article('Artigo 1.º', 'Objeto');

        $this->get(route('sitemap'))
            ->assertSee(route('budgets.articles.index', 2026))
            ->assertSee(route('budgets.articles.show', ['year' => 2026, 'article' => $article->slug]));
    }

    public function test_the_matrix_describes_an_article_row_by_its_number_title_and_law(): void
    {
        $article = $this->article('Artigo 60.º', 'Alteração ao Código do IRS', law: 'Aprova o Código do IRS');
        $matrix = app()->make(BudgetVoteMatrix::class, ['budget' => $this->budget->fresh('legislature'), 'proposals' => [], 'parties' => [], 'cells' => [], 'forArticles' => true]);

        $this->assertSame(['lead' => null, 'code' => 'Artigo 60.º', 'detail' => 'Aprova o Código do IRS'], $matrix->rowEyebrow($article));
        $this->assertSame('Alteração ao Código do IRS', $matrix->rowLabel($article));
        $this->assertSame(route('budgets.articles.show', ['year' => 2026, 'article' => $article->slug]), $matrix->rowUrl($article));
    }

    public function test_an_article_status_reads_in_the_masculine(): void
    {
        $this->assertSame('Aprovado', BudgetProposalStatus::Approved->articleLabel());
        $this->assertSame('Aprovada', BudgetProposalStatus::Approved->label());
    }

    private function syncFixture(): int
    {
        $this->fakeBill($this->fixture());

        return app(BudgetArticleSyncService::class)->sync($this->budget);
    }

    /**
     * @param  array<string, mixed>  $bill
     */
    private function fakeBill(array $bill): void
    {
        Http::fake([self::BILL_URL => Http::response($bill)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fixture(): array
    {
        return json_decode(file_get_contents(base_path('tests/Fixtures/OE2026Or_excerpt.json')), true);
    }

    /**
     * @param  array<string, string>  $positions  the decisive vote, by party
     */
    private function article(string $number, string $title, BudgetProposalStatus $status = BudgetProposalStatus::Pending, array $positions = [], ?string $law = null): BudgetArticle
    {
        static $position = 0;
        $numberSlug = str(str_replace('º', '', $number))->slug()->toString();

        $article = BudgetArticle::factory()->create([
            'budget_id' => $this->budget->id,
            'position' => ++$position,
            'number' => $number,
            'number_slug' => $numberSlug,
            'slug' => str("{$numberSlug} {$title}")->slug()->toString(),
            'title' => $title,
            'status' => $status,
            'law_title' => $law ? 'Decreto-Lei n.º 1/88' : null,
            'law_subtitle' => $law,
        ]);

        if ($positions !== []) {
            $vote = BudgetVote::factory()->create(['budget_proposal_id' => null, 'budget_article_id' => $article->id, 'stage' => BudgetVoteStage::Committee, 'is_final' => true]);

            foreach ($positions as $party => $value) {
                $vote->positions()->create(['party' => $party, 'position' => $value]);
            }
        }

        return $article;
    }
}
