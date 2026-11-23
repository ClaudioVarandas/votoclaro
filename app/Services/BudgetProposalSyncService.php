<?php

namespace App\Services;

use App\Enums\BudgetProposalStatus;
use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\BudgetTheme;
use App\Models\Initiative;
use Closure;
use Exception;
use Illuminate\Support\Facades\DB;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

/**
 * Imports the amendments proposed to a State Budget (OEPropostasAlteracao<year>Or_json.txt),
 * with the vote of each parliamentary group. Running it again updates every amendment in place,
 * which matters while the budget is still being voted.
 */
class BudgetProposalSyncService
{
    private const MAX_SLUG_LENGTH = 90;

    /** @var array<string, int> */
    private array $themeIds = [];

    public function __construct(private ParliamentFileDownloader $downloader, private BudgetVoteWriter $voteWriter) {}

    /**
     * @param  (Closure(int $fileSize): void)|null  $onDownloaded
     * @param  (Closure(int $bytesRead, int $imported): void)|null  $onImported  called after each amendment
     * @return int the number of amendments imported
     */
    public function sync(Budget $budget, ?Closure $onDownloaded = null, ?Closure $onImported = null): int
    {
        if (! $budget->proposals_url) {
            throw new Exception("The {$budget->year} budget has no proposals URL.");
        }

        $this->voteWriter->prepare($budget);
        $this->themeIds = [];

        $imported = $this->downloader->withDownload($budget->proposals_url, function (string $path) use ($budget, $onDownloaded, $onImported): int {
            $onDownloaded?->__invoke(filesize($path));

            $imported = 0;
            $items = Items::fromFile($path, [
                'pointer' => '/PropostaDeAlteracao',
                'decoder' => new ExtJsonDecoder(true),
                'debug' => $onImported !== null,
            ]);

            foreach ($items as $item) {
                DB::transaction(fn () => $this->syncProposal($budget, $item));

                $imported++;
                $onImported?->__invoke($items->getPosition(), $imported);
            }

            return $imported;
        });

        $budget->update([
            'party_order' => $this->voteWriter->partyOrder(),
            'initiative_id' => $budget->initiative_id ?? $this->findInitiativeId($budget),
            'last_imported_at' => now(),
        ]);

        return $imported;
    }

    /**
     * The budget bill of a year, as the initiatives import stores it.
     */
    public function findInitiativeId(Budget $budget): ?string
    {
        return Initiative::query()
            ->where('legislature_id', $budget->legislature_id)
            ->where('initiative_type', 'P')
            ->where('title', 'like', "%Orçamento do Estado para {$budget->year}")
            ->value('id');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function syncProposal(Budget $budget, array $item): void
    {
        $proposal = BudgetProposal::updateOrCreate(
            ['id' => (int) $item['ID']],
            [
                'budget_id' => $budget->id,
                'number' => $item['Numero'],
                'slug' => $this->slug($item['Numero'], $item['Titulo'] ?? null),
                'title' => $item['Titulo'] ?? $item['Numero'],
                'type' => $item['Tipo'] ?? null,
                'scope' => $item['Incide'] ?? null,
                'status' => BudgetProposalStatus::fromLabel($item['Estado'] ?? null),
                'status_label' => $item['Estado'] ?? '',
                'proposer_label' => $item['GrupoParlamentar_Partido'] ?? null,
                'submitted_at' => $this->voteWriter->dateTime($item['Data'] ?? null),
                'pdf_url' => isset($item['Ficheiro']) ? preg_replace('#^http://#', 'https://', $item['Ficheiro']) : null,
                'text' => $this->text($item),
            ],
        );

        $proposal->parties()->sync($this->proposingPartyIds($item));
        $proposal->themes()->sync($this->themeIdsFor($item['Tema'] ?? null));

        $this->voteWriter->replace($proposal->votes(), $item['Votacoes']['Votacao'] ?? null);
    }

    /**
     * The parties of the deputies who signed it, and the ones named as proposers. A proposal of
     * deputies lists their names there instead, which match no party and are left out.
     *
     * @param  array<string, mixed>  $item
     * @return list<int>
     */
    private function proposingPartyIds(array $item): array
    {
        return collect(ParliamentJson::list($item['Proponentes']['Proponente'] ?? null))
            ->map(fn (mixed $proponent): ?string => is_array($proponent) ? ($proponent['GP_Partido'] ?? null) : null)
            ->merge(explode(',', (string) ($item['GrupoParlamentar_Partido'] ?? '')))
            ->filter()
            ->map(fn (string $acronym): ?int => $this->voteWriter->partyByAcronym($acronym)?->id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function themeIdsFor(?string $themes): array
    {
        return collect(explode(';', (string) $themes))
            ->map(fn (string $theme): string => trim(preg_replace('/^Comissão de\s+/u', '', trim($theme))))
            ->filter()
            ->unique()
            ->map(fn (string $theme): int => $this->themeIds[$theme] ??= BudgetTheme::firstOrCreate(
                ['name' => $theme],
                ['slug' => str($theme)->slug()->toString()],
            )->id)
            ->values()
            ->all();
    }

    /**
     * The text of the amended articles, when the file carries it; the full proposal is in its PDF.
     *
     * @param  array<string, mixed>  $item
     */
    private function text(array $item): ?string
    {
        $texts = collect([
            ...ParliamentJson::list($item['Iniciativas_Artigos']['Iniciativa_Artigo'] ?? null),
            ...ParliamentJson::list($item['DiplomasMedidas']['DiplomaMedida'] ?? null),
        ])->map(fn (mixed $part): ?string => is_array($part) ? ParliamentJson::text($part['Texto'] ?? null, "\n") : null);

        return ParliamentJson::text($texts->filter()->all(), "\n\n");
    }

    /**
     * "1907C" and "Artigo 119.º-A (Saúde e direitos das mulheres na menopausa)" make
     * "1907c-saude-e-direitos-das-mulheres-na-menopausa": the number keeps it unique within the
     * budget, the subject makes the address readable.
     */
    private function slug(string $number, ?string $title): string
    {
        $subject = $title !== null && preg_match('/\((.+)\)\s*$/u', $title, $matches) ? $matches[1] : (string) $title;

        return str("{$number} {$subject}")->slug()->limit(self::MAX_SLUG_LENGTH, '')->rtrim('-')->toString();
    }
}
