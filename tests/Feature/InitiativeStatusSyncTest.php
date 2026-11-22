<?php

namespace Tests\Feature;

use App\Enums\InitiativeStatus;
use App\Models\Initiative;
use App\Models\Legislature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InitiativeStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://parlamento.test/IniciativasXVI_json.txt';

    protected function setUp(): void
    {
        parent::setUp();

        Legislature::factory()->create(['sigla' => 'XVI', 'number' => 16, 'initiatives_url' => self::URL]);
    }

    public function test_an_approved_vote_makes_the_initiative_approved(): void
    {
        $this->import([$this->vote('2024-12-20', 'Aprovado')]);

        $this->assertSame(InitiativeStatus::Approved, $this->importedStatus());
    }

    public function test_a_rejected_vote_makes_the_initiative_rejected(): void
    {
        $this->import([$this->vote('2024-12-20', 'Rejeitado')]);

        $this->assertSame(InitiativeStatus::Rejected, $this->importedStatus());
    }

    public function test_an_initiative_without_votes_is_in_progress(): void
    {
        $this->import([]);

        $this->assertSame(InitiativeStatus::InProgress, $this->importedStatus());
    }

    public function test_the_latest_vote_decides(): void
    {
        $this->import([$this->vote('2024-12-20', 'Aprovado'), $this->vote('2025-02-01', 'Rejeitado')]);

        $this->assertSame(InitiativeStatus::Rejected, $this->importedStatus());
    }

    public function test_a_lapsed_phase_outranks_an_earlier_approval(): void
    {
        $this->import([
            $this->vote('2024-12-20', 'Aprovado'),
            ['DataFase' => '2025-03-11', 'CodigoFase' => '649', 'Fase' => 'Iniciativa Caducada', 'Votacao' => null],
        ]);

        $this->assertSame(InitiativeStatus::Lapsed, $this->importedStatus());
        $this->assertSame('2024-12-20', Initiative::query()->findOrFail('700001')->final_vote_date->toDateString());
    }

    public function test_a_withdrawn_phase_makes_the_initiative_withdrawn(): void
    {
        $this->import([
            ['DataFase' => '2024-09-05', 'CodigoFase' => '648', 'Fase' => 'Retirada da iniciativa', 'Votacao' => null],
            ['DataFase' => '2024-09-11', 'CodigoFase' => '21', 'Fase' => 'Anúncio', 'Votacao' => null],
        ]);

        $this->assertSame(InitiativeStatus::Withdrawn, $this->importedStatus());
    }

    public function test_a_reimport_updates_the_status(): void
    {
        Http::fake([self::URL => Http::sequence()
            ->push([$this->initiative([$this->vote('2024-12-20', 'Aprovado')])])
            ->push([$this->initiative([
                $this->vote('2024-12-20', 'Aprovado'),
                ['DataFase' => '2025-06-02', 'CodigoFase' => '649', 'Fase' => 'Iniciativa Caducada', 'Votacao' => null],
            ])]),
        ]);

        $this->artisan('ar:initiatives:import', ['--legislature' => 'XVI'])->assertSuccessful();
        $this->assertSame(InitiativeStatus::Approved, $this->importedStatus());

        $this->artisan('ar:initiatives:import', ['--legislature' => 'XVI'])->assertSuccessful();
        $this->assertSame(InitiativeStatus::Lapsed, $this->importedStatus());
    }

    /**
     * @param  list<array<string, mixed>>  $laterEvents
     */
    private function import(array $laterEvents): void
    {
        Http::fake([self::URL => Http::response([$this->initiative($laterEvents)])]);

        $this->artisan('ar:initiatives:import', ['--legislature' => 'XVI'])->assertSuccessful();
    }

    /**
     * @param  list<array<string, mixed>>  $laterEvents
     * @return array<string, mixed>
     */
    private function initiative(array $laterEvents): array
    {
        return [
            'IniId' => '700001',
            'IniTitulo' => 'Iniciativa de teste',
            'IniTipo' => 'J',
            'IniDescTipo' => 'Projeto de Lei',
            'IniAutorOutros' => null,
            'IniAutorGruposParlamentares' => [['GP' => 'PS']],
            'IniAutorDeputados' => null,
            'IniEventos' => [
                ['DataFase' => '2024-10-01', 'CodigoFase' => '10', 'Fase' => 'Entrada', 'Votacao' => null],
                ...$laterEvents,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vote(string $date, string $result): array
    {
        return [
            'DataFase' => $date,
            'CodigoFase' => '250',
            'Fase' => 'Votação na generalidade',
            'Votacao' => [[
                'id' => 'v'.str_replace('-', '', $date),
                'data' => $date,
                'resultado' => $result,
                'unanime' => null,
                'detalhe' => null,
            ]],
        ];
    }

    private function importedStatus(): InitiativeStatus
    {
        return Initiative::query()->findOrFail('700001')->status;
    }
}
