<?php

namespace Tests\Feature;

use App\Models\Initiative;
use App\Models\Legislature;
use App\Models\Party;
use App\Services\ParliamentInitiativeSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LegislatureCommandsTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_INFO_URL = 'https://parlamento.test/InformacaoBaseXVIII_json.txt';

    private const INITIATIVES_URL = 'https://parlamento.test/IniciativasXVIII_json.txt';

    public function test_register_creates_the_legislature_and_imports_its_base_information(): void
    {
        Http::fake([self::BASE_INFO_URL => Http::response($this->baseInfo('XVIII', '2029-04-01', null, ['PS' => 'Partido Socialista', 'NOVO' => 'Partido Novo']))]);

        $this->artisan('ar:legislature:register', [
            'sigla' => 'xviii',
            '--initiatives-url' => self::INITIATIVES_URL,
            '--base-info-url' => self::BASE_INFO_URL,
        ])->assertSuccessful();

        $legislature = Legislature::query()->where('sigla', 'XVIII')->firstOrFail();
        $this->assertSame(18, $legislature->number);
        $this->assertSame(self::INITIATIVES_URL, $legislature->initiatives_url);
        $this->assertSame('2029-04-01', $legislature->start_date->toDateString());
        $this->assertNull($legislature->end_date);
        $this->assertEqualsCanonicalizing(['PS', 'NOVO'], $legislature->parties()->pluck('acronym')->all());
    }

    public function test_register_updates_the_urls_of_an_existing_legislature(): void
    {
        $legislature = Legislature::factory()->xvii()->create(['base_info_url' => null]);

        $this->artisan('ar:legislature:register', ['sigla' => 'XVII', '--initiatives-url' => self::INITIATIVES_URL])
            ->assertSuccessful();

        $this->assertSame(self::INITIATIVES_URL, $legislature->fresh()->initiatives_url);
        $this->assertSame(1, Legislature::query()->count());
    }

    public function test_register_without_a_base_information_url_skips_the_import(): void
    {
        Http::fake();

        $this->artisan('ar:legislature:register', ['sigla' => 'XVIII'])->assertSuccessful();

        $this->assertDatabaseHas('legislatures', ['sigla' => 'XVIII', 'number' => 18, 'start_date' => null]);
        Http::assertNothingSent();
    }

    public function test_register_rejects_a_sigla_that_is_not_a_roman_numeral(): void
    {
        $this->artisan('ar:legislature:register', ['sigla' => '18'])->assertFailed();

        $this->assertDatabaseCount('legislatures', 0);
    }

    public function test_base_info_import_closes_the_legislature_and_keeps_its_earlier_parties(): void
    {
        $legislature = Legislature::factory()->xvii()->withParties(['PS', 'GONE'])->create(['base_info_url' => self::BASE_INFO_URL]);
        Http::fake([self::BASE_INFO_URL => Http::response($this->baseInfo('XVII', '2025-06-03', '2029-03-31', ['PS' => 'Partido Socialista', 'L' => 'Livre']))]);

        $this->artisan('ar:base_info:import')->assertSuccessful();

        $legislature->refresh();
        $this->assertSame('2029-03-31', $legislature->end_date->toDateString());
        $this->assertTrue($legislature->isClosed());
        $this->assertEqualsCanonicalizing(['PS', 'GONE', 'L'], $legislature->parties()->pluck('acronym')->all());
        $this->assertSame('Partido Socialista', Party::query()->where('acronym', 'PS')->value('name'));
    }

    public function test_base_info_import_refuses_data_of_another_legislature(): void
    {
        $legislature = Legislature::factory()->xvii()->create(['base_info_url' => self::BASE_INFO_URL]);
        Http::fake([self::BASE_INFO_URL => Http::response($this->baseInfo('XVIII', '2029-04-01', null, ['PS' => 'Partido Socialista']))]);

        $this->artisan('ar:base_info:import', ['--legislature' => 'XVII'])->assertFailed();

        $this->assertSame('2025-06-03', $legislature->fresh()->start_date->toDateString());
        $this->assertSame(0, $legislature->parties()->count());
    }

    public function test_base_info_import_fails_for_an_unknown_legislature(): void
    {
        $this->artisan('ar:base_info:import', ['--legislature' => 'XX'])->assertFailed();
    }

    public function test_initiatives_import_defaults_to_the_current_legislature(): void
    {
        Legislature::factory()->create(['sigla' => 'XVI', 'number' => 16, 'initiatives_url' => 'https://parlamento.test/old.txt']);
        $current = Legislature::factory()->create(['sigla' => 'XVIII', 'number' => 18, 'end_date' => null, 'initiatives_url' => self::INITIATIVES_URL]);
        Http::fake([self::INITIATIVES_URL => Http::response([$this->initiative('900001')])]);

        $this->artisan('ar:initiatives:import')->assertSuccessful();

        $this->assertSame($current->id, Initiative::query()->findOrFail('900001')->legislature_id);
        Http::assertSentCount(1);
    }

    public function test_initiatives_import_of_a_named_legislature(): void
    {
        $previous = Legislature::factory()->create(['sigla' => 'XVI', 'number' => 16, 'initiatives_url' => 'https://parlamento.test/old.txt']);
        Legislature::factory()->create(['sigla' => 'XVIII', 'number' => 18, 'end_date' => null, 'initiatives_url' => self::INITIATIVES_URL]);
        Http::fake(['https://parlamento.test/old.txt' => Http::response([$this->initiative('800001')])]);

        $this->artisan('ar:initiatives:import', ['--legislature' => 'xvi'])->assertSuccessful();

        $this->assertSame($previous->id, Initiative::query()->findOrFail('800001')->legislature_id);
    }

    public function test_initiatives_import_reads_every_initiative_and_removes_the_download(): void
    {
        Legislature::factory()->xvii()->create(['initiatives_url' => self::INITIATIVES_URL]);
        Http::fake([self::INITIATIVES_URL => Http::response([
            $this->initiative('900001'),
            $this->initiative('900002'),
            $this->initiative('900003'),
        ])]);
        $downloadsBefore = glob(sys_get_temp_dir().'/ar-initiatives-*');

        $this->artisan('ar:initiatives:import')
            ->expectsOutputToContain('Importing...')
            ->expectsOutputToContain('Sync completed: 3 initiatives imported.')
            ->assertSuccessful();

        $this->assertEqualsCanonicalizing(['900001', '900002', '900003'], Initiative::query()->pluck('id')->all());
        $this->assertSame($downloadsBefore, glob(sys_get_temp_dir().'/ar-initiatives-*'));
    }

    public function test_sync_reports_its_progress_through_the_file(): void
    {
        $legislature = Legislature::factory()->xvii()->create(['initiatives_url' => self::INITIATIVES_URL]);
        Http::fake([self::INITIATIVES_URL => Http::response([
            $this->initiative('900001'),
            $this->initiative('900002'),
        ])]);
        $fileSize = null;
        $progress = [];

        $imported = app(ParliamentInitiativeSyncService::class)->sync(
            $legislature,
            function (int $size) use (&$fileSize): void {
                $fileSize = $size;
            },
            function (int $bytesRead, int $count) use (&$progress): void {
                $progress[] = [$bytesRead, $count];
            },
        );

        $this->assertSame(2, $imported);
        $this->assertGreaterThan(0, $fileSize);
        $this->assertSame([1, 2], array_column($progress, 1));
        $this->assertGreaterThan(0, $progress[0][0]);
        $this->assertGreaterThan($progress[0][0], $progress[1][0]);
        $this->assertLessThanOrEqual($fileSize, $progress[1][0]);
    }

    public function test_sync_without_progress_callbacks_still_counts_the_initiatives(): void
    {
        $legislature = Legislature::factory()->xvii()->create(['initiatives_url' => self::INITIATIVES_URL]);
        Http::fake([self::INITIATIVES_URL => Http::response([
            $this->initiative('900001'),
            $this->initiative('900002'),
        ])]);

        $this->assertSame(2, app(ParliamentInitiativeSyncService::class)->sync($legislature));
    }

    public function test_initiatives_import_removes_the_download_when_it_fails(): void
    {
        Legislature::factory()->xvii()->create(['initiatives_url' => self::INITIATIVES_URL]);
        Http::fake([self::INITIATIVES_URL => Http::response('Service Unavailable', 503)]);
        $downloadsBefore = glob(sys_get_temp_dir().'/ar-initiatives-*');

        $this->artisan('ar:initiatives:import')->assertFailed();

        $this->assertSame($downloadsBefore, glob(sys_get_temp_dir().'/ar-initiatives-*'));
    }

    public function test_initiatives_import_fails_without_any_legislature(): void
    {
        Http::fake();

        $this->artisan('ar:initiatives:import')->assertFailed();

        Http::assertNothingSent();
    }

    public function test_initiatives_import_fails_when_the_source_is_unavailable(): void
    {
        Legislature::factory()->xvii()->create(['initiatives_url' => self::INITIATIVES_URL]);
        Http::fake([self::INITIATIVES_URL => Http::response('', 500)]);

        $this->artisan('ar:initiatives:import')->assertFailed();
    }

    /**
     * @param  array<string, string>  $groups
     * @return array<string, mixed>
     */
    private function baseInfo(string $sigla, string $start, ?string $end, array $groups): array
    {
        return [
            'DetalheLegislatura' => ['id' => '109', 'sigla' => $sigla, 'siglaAntiga' => $sigla, 'dtini' => $start, 'dtfim' => $end],
            'GruposParlamentares' => collect($groups)->map(fn (string $name, string $acronym): array => ['sigla' => $acronym, 'nome' => $name])->values()->all(),
            'SessoesLegislativas' => [],
            'Deputados' => [],
            'CirculosEleitorais' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function initiative(string $id): array
    {
        return [
            'IniId' => $id,
            'IniTitulo' => "Iniciativa {$id}",
            'IniTipo' => 'R',
            'IniDescTipo' => 'Projeto de Resolução',
            'IniAutorOutros' => ['nome' => 'Governo'],
            'IniAutorGruposParlamentares' => null,
            'IniAutorDeputados' => null,
            'IniEventos' => [
                ['DataFase' => '2029-05-01', 'CodigoFase' => '10', 'Fase' => 'Entrada', 'Votacao' => null],
            ],
        ];
    }
}
