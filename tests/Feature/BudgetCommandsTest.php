<?php

namespace Tests\Feature;

use App\Enums\BudgetProposalStatus;
use App\Enums\BudgetVotePosition;
use App\Enums\BudgetVoteStage;
use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\BudgetVote;
use App\Models\Initiative;
use App\Models\Legislature;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class BudgetCommandsTest extends TestCase
{
    use RefreshDatabase;

    private const PROPOSALS_URL = 'https://parlamento.test/OEPropostasAlteracao2027Or_json.txt';

    private Legislature $legislature;

    protected function setUp(): void
    {
        parent::setUp();

        $this->legislature = Legislature::factory()->xvii()->withParties()->create();
    }

    public function test_register_creates_the_budget_in_the_current_legislature_and_links_its_bill(): void
    {
        $bill = Initiative::factory()->create([
            'legislature_id' => $this->legislature->id,
            'title' => 'Orçamento do Estado para 2027',
            'initiative_type' => 'P',
        ]);

        $this->artisan('ar:budget:register', ['year' => '2027', '--proposals-url' => self::PROPOSALS_URL])
            ->expectsOutputToContain('Registered the 2027 budget of the XVII legislature.')
            ->assertSuccessful();

        $budget = Budget::query()->sole();
        $this->assertSame($this->legislature->id, $budget->legislature_id);
        $this->assertSame(Budget::KIND_ORDINARY, $budget->kind);
        $this->assertSame(self::PROPOSALS_URL, $budget->proposals_url);
        $this->assertSame($bill->id, $budget->initiative_id);
    }

    public function test_register_updates_the_urls_of_an_existing_budget(): void
    {
        $budget = Budget::factory()->create(['proposals_url' => null]);

        $this->artisan('ar:budget:register', ['year' => '2027', '--proposals-url' => self::PROPOSALS_URL])
            ->expectsOutputToContain('Updated the 2027 budget')
            ->assertSuccessful();

        $this->assertSame(self::PROPOSALS_URL, $budget->fresh()->proposals_url);
        $this->assertSame(1, Budget::query()->count());
    }

    public function test_register_rejects_a_budget_already_in_another_legislature(): void
    {
        $previous = Legislature::factory()->create(['sigla' => 'XVI', 'number' => 16]);
        Budget::factory()->create(['legislature_id' => $this->legislature->id, 'year' => 2025]);

        $this->artisan('ar:budget:register', ['year' => '2025', '--legislature' => 'XVI'])->assertFailed();

        $this->assertSame(0, $previous->budgets()->count());
    }

    public function test_register_rejects_what_is_not_a_year(): void
    {
        $this->artisan('ar:budget:register', ['year' => 'OE27'])->assertFailed();

        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_register_fails_for_an_unknown_legislature(): void
    {
        $this->artisan('ar:budget:register', ['year' => '2027', '--legislature' => 'XX'])->assertFailed();
    }

    public function test_import_reads_every_amendment(): void
    {
        $this->importFixture()->assertSuccessful();

        $this->assertSame(11, BudgetProposal::query()->count());
    }

    public function test_import_shows_progress_and_the_count(): void
    {
        $this->importFixture()
            ->expectsOutputToContain('Downloading the amendments to the 2027 budget...')
            ->expectsOutputToContain('Importing...')
            ->expectsOutputToContain('Sync completed: 11 amendments imported.')
            ->assertSuccessful();
    }

    public function test_import_matches_the_menopause_votes_published_by_vidas(): void
    {
        $this->importFixture();

        // The VIDAs infographic of the committee votes of 20 November 2026: PSD, CH, PS, IL, L, PCP, CDS-PP, BE, PAN.
        $expected = [
            '1907C' => ['contra', 'abstencao', 'favor', 'favor', 'favor', 'favor', 'abstencao', 'favor', 'favor'],
            '2211C' => ['contra', 'abstencao', 'abstencao', 'abstencao', 'favor', 'favor', 'contra', 'favor', 'favor'],
            '2215C' => ['contra', 'abstencao', 'favor', 'favor', 'favor', 'favor', 'contra', 'favor', 'favor'],
            '2772C' => ['contra', 'favor', 'abstencao', 'favor', 'abstencao', 'abstencao', 'contra', 'favor', 'favor'],
            '743C' => ['contra', 'abstencao', 'abstencao', 'abstencao', 'favor', 'favor', 'contra', 'favor', 'favor'],
            '769C' => ['contra', 'abstencao', 'favor', 'favor', 'favor', 'favor', 'contra', 'favor', 'favor'],
        ];

        foreach ($expected as $number => $positions) {
            $proposal = BudgetProposal::query()->where('number', $number)->sole();
            $vote = $proposal->votes()->sole();

            $this->assertSame(BudgetProposalStatus::Rejected, $proposal->status, $number);
            $this->assertSame(BudgetVoteStage::Committee, $vote->stage, $number);
            $this->assertSame(
                array_combine(['PSD', 'CH', 'PS', 'IL', 'L', 'PCP', 'CDS-PP', 'BE', 'PAN'], $positions),
                $vote->positions->mapWithKeys(fn ($position): array => [$position->party => $position->position->value])->all(),
                $number,
            );
        }
    }

    public function test_import_keeps_an_amendment_still_waiting_for_its_vote(): void
    {
        $this->importFixture();

        $proposal = BudgetProposal::query()->where('number', '726C')->sole();
        $this->assertSame(BudgetProposalStatus::Pending, $proposal->status);
        $this->assertSame('Aguarda Voto em Comissão', $proposal->status_label);
        $this->assertSame(0, $proposal->votes()->count());
    }

    public function test_import_keeps_the_committee_and_the_plenary_vote_of_an_amendment_called_up(): void
    {
        $this->importFixture();

        $proposal = BudgetProposal::query()->where('number', '2770C')->sole();
        $this->assertSame(BudgetProposalStatus::Approved, $proposal->status);
        $this->assertEqualsCanonicalizing(
            [BudgetVoteStage::Plenary, BudgetVoteStage::Committee],
            $proposal->votes->pluck('stage')->all(),
        );

        $this->assertSame([BudgetVoteStage::Plenary], $proposal->finalVotes->pluck('stage')->all());

        $plenary = $proposal->votes->firstWhere('stage', BudgetVoteStage::Plenary);
        $this->assertSame('2026-11-19', $plenary->date->toDateString());
        $this->assertSame(BudgetVotePosition::Contra, $plenary->positions->firstWhere('party', 'JPP')->position);
    }

    public function test_import_records_a_deputy_who_voted_apart_from_the_group(): void
    {
        $this->importFixture();

        $positions = BudgetProposal::query()->where('number', '1740C')->sole()->votes()->sole()->positions;

        $this->assertSame(BudgetVotePosition::Contra, $positions->where('party', 'PSD')->whereNull('deputy')->sole()->position);
        $deputy = $positions->whereNotNull('deputy')->sole();
        $this->assertSame('Pedro Coelho', $deputy->deputy);
        $this->assertSame('PSD', $deputy->party);
        $this->assertSame(BudgetVotePosition::Favor, $deputy->position);
    }

    public function test_the_committee_votes_decide_a_proposal_not_called_up(): void
    {
        $this->importFixture();

        $proposal = BudgetProposal::query()->where('number', '1907C')->sole();

        $this->assertSame([BudgetVoteStage::Committee], $proposal->finalVotes->pluck('stage')->all());
    }

    public function test_import_skips_a_vote_recorded_without_positions(): void
    {
        $this->importFixture();

        $this->assertSame(1, BudgetProposal::query()->where('number', '2297C')->sole()->votes()->count());
    }

    public function test_import_links_a_joint_proposal_to_both_parties(): void
    {
        $this->importFixture();

        $proposal = BudgetProposal::query()->where('number', '2742C-1')->sole();

        $this->assertEqualsCanonicalizing(['PSD', 'CDS-PP'], $proposal->parties->pluck('acronym')->all());
        $this->assertStringStartsWith('2742c-1-', $proposal->slug);
    }

    public function test_import_links_a_proposal_of_deputies_to_their_party(): void
    {
        $this->importFixture();

        $this->assertSame(['PSD'], BudgetProposal::query()->where('number', '1740C')->sole()->parties->pluck('acronym')->all());
    }

    public function test_import_builds_a_readable_unique_slug(): void
    {
        $this->importFixture();

        $this->assertSame('1907c-saude-e-direitos-das-mulheres-na-menopausa', BudgetProposal::query()->where('number', '1907C')->sole()->slug);
    }

    public function test_import_files_the_amendment_under_each_of_its_themes(): void
    {
        $this->importFixture();

        $this->assertSame(['Saúde'], BudgetProposal::query()->where('number', '1907C')->sole()->themes->pluck('name')->all());
        $this->assertEqualsCanonicalizing(
            ['Ambiente e Energia', 'Orçamento, Finanças e Administração Pública'],
            BudgetProposal::query()->where('number', '1740C')->sole()->themes->pluck('name')->all(),
        );
    }

    public function test_import_remembers_the_order_the_parliament_lists_the_parties_in(): void
    {
        $this->importFixture();

        $this->assertSame(
            ['PSD', 'CH', 'PS', 'IL', 'L', 'PCP', 'CDS-PP', 'BE', 'PAN', 'JPP'],
            Budget::query()->sole()->party_order,
        );
    }

    public function test_import_links_the_bill_once_it_was_imported(): void
    {
        $budget = Budget::factory()->create(['party_order' => null]);
        $bill = Initiative::factory()->create([
            'legislature_id' => $this->legislature->id,
            'title' => 'Orçamento do Estado para 2027',
            'initiative_type' => 'P',
        ]);

        $this->importFixture();

        $this->assertSame($bill->id, $budget->fresh()->initiative_id);
        $this->assertNotNull($budget->fresh()->last_imported_at);
    }

    public function test_a_second_import_updates_without_duplicating(): void
    {
        Budget::factory()->create(['party_order' => null]);
        Http::fake([self::PROPOSALS_URL => Http::sequence()
            ->push($this->fixture())
            ->push($this->fixtureWith('726C', fn (array $item): array => [
                ...$item,
                'Estado' => 'Rejeitado(a) em Comissão',
                'Votacoes' => $this->fixtureItem('743C')['Votacoes'],
            ])),
        ]);

        $this->artisan('ar:budget:import')->assertSuccessful();
        $votes = BudgetVote::query()->count();
        $this->artisan('ar:budget:import')->assertSuccessful();

        $this->assertSame(11, BudgetProposal::query()->count());
        $this->assertSame(BudgetProposalStatus::Rejected, BudgetProposal::query()->where('number', '726C')->sole()->status);
        $this->assertSame($votes + 1, BudgetVote::query()->count());
    }

    public function test_import_of_a_named_year(): void
    {
        Budget::factory()->create(['year' => 2026, 'proposals_url' => 'https://parlamento.test/2026.txt']);
        Budget::factory()->create(['party_order' => null]);
        Http::fake([self::PROPOSALS_URL => Http::response($this->fixture())]);

        $this->artisan('ar:budget:import', ['--year' => '2027'])->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_import_fails_without_any_budget(): void
    {
        Http::fake();

        $this->artisan('ar:budget:import')->assertFailed();

        Http::assertNothingSent();
    }

    public function test_import_fails_and_removes_the_download_when_the_source_is_unavailable(): void
    {
        Budget::factory()->create();
        Http::fake([self::PROPOSALS_URL => Http::response('Service Unavailable', 503)]);
        $downloadsBefore = glob(sys_get_temp_dir().'/ar-download-*');

        $this->artisan('ar:budget:import')->assertFailed();

        $this->assertSame($downloadsBefore, glob(sys_get_temp_dir().'/ar-download-*'));
    }

    private function importFixture(): PendingCommand
    {
        if (! Budget::query()->exists()) {
            Budget::factory()->create(['party_order' => null]);
        }

        Http::fake([self::PROPOSALS_URL => Http::response($this->fixture())]);

        return $this->artisan('ar:budget:import');
    }

    /**
     * @return array<string, mixed>
     */
    private function fixture(): array
    {
        return json_decode(file_get_contents(base_path('tests/Fixtures/OEPropostasAlteracao2027Or_excerpt.json')), true);
    }

    /**
     * @return array<string, mixed>
     */
    private function fixtureItem(string $number): array
    {
        return collect($this->fixture()['PropostaDeAlteracao'])->firstWhere('Numero', $number);
    }

    /**
     * @param  Closure(array<string, mixed>): array<string, mixed>  $change
     * @return array<string, mixed>
     */
    private function fixtureWith(string $number, Closure $change): array
    {
        return ['PropostaDeAlteracao' => collect($this->fixture()['PropostaDeAlteracao'])
            ->map(fn (array $item): array => $item['Numero'] === $number ? $change($item) : $item)
            ->all()];
    }
}
