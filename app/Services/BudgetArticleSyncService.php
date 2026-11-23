<?php

namespace App\Services;

use App\Enums\BudgetProposalStatus;
use App\Models\Budget;
use App\Models\BudgetArticle;
use App\Models\BudgetProposal;
use Closure;
use Exception;
use Illuminate\Support\Facades\DB;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

/**
 * Imports the articles of the Government's budget bill (OE<year>Or_json.txt) with their votes and
 * the amendments proposed to each. The file also lists the laws the amendments would change and
 * the budget maps, which carry no votes of their own: the maps are voted within the first article.
 * Runs after the amendments, so the links to them can be kept.
 */
class BudgetArticleSyncService
{
    private const ARTICLE_TYPE = 'Iniciativas/Artigos';

    private const MAX_SLUG_LENGTH = 90;

    /** @var array<int, true> */
    private array $knownProposalIds = [];

    /** @var array<string, true> the slugs given in this import */
    private array $usedSlugs = [];

    public function __construct(private ParliamentFileDownloader $downloader, private BudgetVoteWriter $voteWriter) {}

    /**
     * @param  (Closure(int $fileSize): void)|null  $onDownloaded
     * @param  (Closure(int $bytesRead, int $imported): void)|null  $onImported  called after each article
     * @return int the number of articles imported
     */
    public function sync(Budget $budget, ?Closure $onDownloaded = null, ?Closure $onImported = null): int
    {
        if (! $budget->budget_url) {
            throw new Exception("The {$budget->year} budget has no bill URL.");
        }

        $this->voteWriter->prepare($budget);
        $this->usedSlugs = [];
        $this->knownProposalIds = array_fill_keys(BudgetProposal::query()->where('budget_id', $budget->id)->pluck('id')->all(), true);

        $imported = $this->downloader->withDownload($budget->budget_url, function (string $path) use ($budget, $onDownloaded, $onImported): int {
            $onDownloaded?->__invoke(filesize($path));

            $imported = 0;
            $seen = [];
            $items = Items::fromFile($path, [
                'pointer' => '/Item',
                'decoder' => new ExtJsonDecoder(true),
                'debug' => $onImported !== null,
            ]);

            foreach ($items as $item) {
                if (($item['Tipo'] ?? null) !== self::ARTICLE_TYPE) {
                    continue;
                }

                $imported++;
                $seen[] = (int) $item['ID'];
                DB::transaction(fn () => $this->syncArticle($budget, $item, $imported));
                $onImported?->__invoke($items->getPosition(), $imported);
            }

            // An article the Government's bill no longer has, as when a renumbering replaced it.
            BudgetArticle::query()->where('budget_id', $budget->id)->whereNotIn('id', $seen)->delete();

            return $imported;
        });

        $budget->update(['party_order' => $this->voteWriter->partyOrder()]);

        return $imported;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function syncArticle(Budget $budget, array $item, int $position): void
    {
        $number = trim((string) $item['Numero']);
        $law = ParliamentJson::list($item['DiplomasaModificar']['DiplomaaModificar'] ?? null)[0] ?? null;

        $article = BudgetArticle::updateOrCreate(
            ['id' => (int) $item['ID']],
            [
                'budget_id' => $budget->id,
                'position' => $position,
                'number' => $number,
                'number_slug' => $this->numberSlug($number),
                'slug' => $this->uniqueSlug($budget, (int) $item['ID'], $number, $item['Titulo'] ?? null),
                'title' => ParliamentJson::text($item['Titulo'] ?? null),
                'text' => $this->text($item['Texto'] ?? null),
                'status' => BudgetProposalStatus::fromLabel($item['Estado'] ?? null),
                'status_label' => $item['Estado'] ?? '',
                'law_title' => is_array($law) ? ParliamentJson::text($law['DiplomaTitulo'] ?? null) : null,
                'law_subtitle' => is_array($law) ? ParliamentJson::text($law['DiplomaSubTitulo'] ?? null) : null,
            ],
        );

        $article->proposals()->sync($this->proposalIds($item));
        $this->voteWriter->replace($article->votes(), $item['Votacoes']['Votacao'] ?? null);
    }

    /**
     * The amendments the file links to the article, among the ones imported.
     *
     * @param  array<string, mixed>  $item
     * @return list<int>
     */
    private function proposalIds(array $item): array
    {
        return collect(ParliamentJson::list($item['PropostasDeAlteracao']['Proposta'] ?? null))
            ->map(fn (mixed $proposal): ?int => is_array($proposal) && isset($proposal['ID_PA']) ? (int) $proposal['ID_PA'] : null)
            ->filter(fn (?int $id): bool => $id !== null && isset($this->knownProposalIds[$id]))
            ->unique()
            ->values()
            ->all();
    }

    private function text(mixed $text): ?string
    {
        $text = ParliamentJson::text($text, "\n");

        return $text === null ? null : str_replace(["\r\n", "\r"], "\n", $text);
    }

    /**
     * "Artigo 38.º-A" makes "artigo-38-a".
     */
    private function numberSlug(string $number): string
    {
        return str(str_replace(['º', 'ª'], '', $number))->slug()->toString();
    }

    /**
     * "Artigo 60.º" and "Alteração ao Código do IRS" make "artigo-60-alteracao-ao-codigo-do-irs".
     * The articles added by approved amendments share a number until the final text renumbers
     * them ("Artigo 22.º-A" five times), and at times a title too, so a repeated address gets the
     * article's id.
     */
    private function uniqueSlug(Budget $budget, int $id, string $number, ?string $title): string
    {
        $slug = str($this->numberSlug($number).' '.$title)->slug()->limit(self::MAX_SLUG_LENGTH, '')->rtrim('-')->toString();

        $taken = isset($this->usedSlugs[$slug]) || BudgetArticle::query()
            ->where('budget_id', $budget->id)
            ->where('slug', $slug)
            ->where('id', '!=', $id)
            ->exists();

        if ($taken) {
            $slug = "{$slug}-{$id}";
        }

        $this->usedSlugs[$slug] = true;

        return $slug;
    }
}
