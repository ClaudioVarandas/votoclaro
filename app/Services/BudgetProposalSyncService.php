<?php

namespace App\Services;

use App\Enums\BudgetProposalStatus;
use App\Enums\BudgetVotePosition;
use App\Enums\BudgetVoteStage;
use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\BudgetTheme;
use App\Models\Initiative;
use App\Models\Party;
use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Support\Collection;
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

    /** @var Collection<string, Party> parties by lower-case name */
    private Collection $partiesByName;

    /** @var Collection<string, Party> parties by upper-case acronym */
    private Collection $partiesByAcronym;

    /** @var array<string, int> */
    private array $themeIds = [];

    /** @var list<string> */
    private array $partyOrder = [];

    public function __construct(private ParliamentFileDownloader $downloader) {}

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

        $parties = Party::all();
        $this->partiesByName = $parties->keyBy(fn (Party $party): string => mb_strtolower($party->name));
        $this->partiesByAcronym = $parties->keyBy(fn (Party $party): string => mb_strtoupper($party->acronym));
        $this->themeIds = [];
        $this->partyOrder = $budget->party_order ?? [];

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
            'party_order' => $this->partyOrder,
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
                'submitted_at' => $this->dateTime($item['Data'] ?? null),
                'pdf_url' => isset($item['Ficheiro']) ? preg_replace('#^http://#', 'https://', $item['Ficheiro']) : null,
                'text' => $this->text($item),
            ],
        );

        $proposal->parties()->sync($this->proposingPartyIds($item));
        $proposal->themes()->sync($this->themeIdsFor($item['Tema'] ?? null));

        $proposal->votes()->delete();

        foreach (array_values(array_filter(ParliamentJson::list($item['Votacoes']['Votacao'] ?? null), 'is_array')) as $sequence => $vote) {
            $this->syncVote($proposal, $sequence + 1, $vote);
        }
    }

    /**
     * @param  array<string, mixed>  $vote
     */
    private function syncVote(BudgetProposal $proposal, int $sequence, array $vote): void
    {
        $groups = ParliamentJson::list($vote['GruposParlamentares']['GrupoParlamentar'] ?? null);
        $positions = ParliamentJson::list($vote['GruposParlamentares']['Voto'] ?? null);

        // Some votes are recorded twice, once without anyone's position: only the full one counts.
        if ($groups === []) {
            return;
        }

        $resultLabel = $vote['ResultadoCompleto'] ?? $vote['Resultado'] ?? '';

        $budgetVote = $proposal->votes()->create([
            'sequence' => $sequence,
            'stage' => BudgetVoteStage::fromResultLabel($resultLabel),
            'date' => $this->dateTime($vote['Data'] ?? null)?->toDateString(),
            'result' => BudgetProposalStatus::fromLabel($resultLabel),
            'result_label' => $resultLabel,
            'subject' => $this->voteSubject($vote),
        ]);

        $acronyms = [];

        foreach ($groups as $index => $group) {
            $position = BudgetVotePosition::fromLabel((string) ($positions[$index] ?? ''));

            if ($position === null) {
                continue;
            }

            $voter = $this->voter((string) $group);

            $budgetVote->positions()->create([...$voter, 'position' => $position]);

            if ($voter['deputy'] === null) {
                $acronyms[] = $voter['party'];
            }
        }

        $this->rememberPartyOrder($acronyms);
    }

    /**
     * A parliamentary group comes by its full name ("Partido Social Democrata"); a deputy who
     * voted apart from it as "Name(PSD)"; an independent deputy as "Não Inscrito".
     *
     * @return array{party: ?string, deputy: ?string}
     */
    private function voter(string $name): array
    {
        $name = trim($name);
        $party = $this->partiesByName->get(mb_strtolower($name));

        if ($party) {
            return ['party' => $party->acronym, 'deputy' => null];
        }

        if (preg_match('/^(.+?)\s*\(([^()]+)\)$/u', $name, $matches)) {
            $acronym = $this->partyByAcronym($matches[2])?->acronym ?? trim($matches[2]);

            return ['party' => $acronym, 'deputy' => mb_convert_case(trim($matches[1]), MB_CASE_TITLE)];
        }

        return ['party' => null, 'deputy' => $name];
    }

    /**
     * The order the Parliament lists the groups in, by size, which the vote grid follows. Groups
     * that only vote in the plenary, such as single-deputy ones, come after.
     *
     * @param  list<string>  $acronyms
     */
    private function rememberPartyOrder(array $acronyms): void
    {
        foreach ($acronyms as $acronym) {
            if (! in_array($acronym, $this->partyOrder, true)) {
                $this->partyOrder[] = $acronym;
            }
        }
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
            ->map(fn (string $acronym): ?int => $this->partyByAcronym($acronym)?->id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The signatories' party comes cut short at times ("CDS-P" for CDS-PP), so a prefix that
     * names a single party counts too.
     */
    private function partyByAcronym(string $acronym): ?Party
    {
        $acronym = mb_strtoupper(trim($acronym));

        if ($acronym === '') {
            return null;
        }

        if ($party = $this->partiesByAcronym->get($acronym)) {
            return $party;
        }

        $matches = $this->partiesByAcronym->filter(fn (Party $party, string $known): bool => str_starts_with($known, $acronym));

        return $matches->count() === 1 ? $matches->first() : null;
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
     * @param  array<string, mixed>  $vote
     */
    private function voteSubject(array $vote): ?string
    {
        $subject = ParliamentJson::text($vote['Descricoes']['Descricao'] ?? null);
        $detail = ParliamentJson::text($vote['SubDescricao'] ?? null);

        return ParliamentJson::text(array_filter([$subject, $detail]), ' — ');
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

    private function dateTime(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return Carbon::createFromFormat('d/m/Y H:i:s', $value) ?: null;
    }
}
