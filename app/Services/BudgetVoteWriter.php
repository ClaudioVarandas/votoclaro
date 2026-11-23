<?php

namespace App\Services;

use App\Enums\BudgetProposalStatus;
use App\Enums\BudgetVotePosition;
use App\Enums\BudgetVoteStage;
use App\Models\Budget;
use App\Models\Party;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Writes the votes of the State Budget files, which record them alike for an amendment and for an
 * article of the Government's bill: each vote with the position of every parliamentary group, and
 * the deputies who voted apart from theirs.
 */
class BudgetVoteWriter
{
    /** @var Collection<string, Party> parties by lower-case name */
    private Collection $partiesByName;

    /** @var Collection<string, Party> parties by upper-case acronym */
    private Collection $partiesByAcronym;

    /** @var list<string> */
    private array $partyOrder = [];

    /**
     * Reads the parties once for a budget's import.
     */
    public function prepare(Budget $budget): void
    {
        $parties = Party::all();
        $this->partiesByName = $parties->keyBy(fn (Party $party): string => mb_strtolower($party->name));
        $this->partiesByAcronym = $parties->keyBy(fn (Party $party): string => mb_strtoupper($party->acronym));
        $this->partyOrder = $budget->party_order ?? [];
    }

    /**
     * Replaces the votes of an amendment or an article with the ones in the file.
     *
     * @param  HasMany<\App\Models\BudgetVote, \Illuminate\Database\Eloquent\Model>  $votes
     * @param  mixed  $fileVotes  the "Votacoes.Votacao" of the item: one vote, a list, or nothing
     */
    public function replace(HasMany $votes, mixed $fileVotes): void
    {
        (clone $votes)->delete();

        foreach (array_values(array_filter(ParliamentJson::list($fileVotes), 'is_array')) as $sequence => $vote) {
            $this->write($votes, $sequence + 1, $vote);
        }

        $this->markFinal($votes);
    }

    /**
     * The order the Parliament lists the groups in, by size, which the vote grid follows. Groups
     * that only vote in the plenary, such as single-deputy ones, come after.
     *
     * @return list<string>
     */
    public function partyOrder(): array
    {
        return $this->partyOrder;
    }

    /**
     * A party by its acronym. The signatories' party comes cut short at times ("CDS-P" for
     * CDS-PP), so a prefix that names a single party counts too.
     */
    public function partyByAcronym(string $acronym): ?Party
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

    public function dateTime(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return Carbon::createFromFormat('d/m/Y H:i:s', $value) ?: null;
    }

    /**
     * @param  HasMany<\App\Models\BudgetVote, \Illuminate\Database\Eloquent\Model>  $votes
     * @param  array<string, mixed>  $vote
     */
    private function write(HasMany $votes, int $sequence, array $vote): void
    {
        $groups = ParliamentJson::list($vote['GruposParlamentares']['GrupoParlamentar'] ?? null);
        $positions = ParliamentJson::list($vote['GruposParlamentares']['Voto'] ?? null);

        // Some votes are recorded twice, once without anyone's position: only the full one counts.
        if ($groups === []) {
            return;
        }

        $resultLabel = $vote['ResultadoCompleto'] ?? $vote['Resultado'] ?? '';

        $budgetVote = (clone $votes)->create([
            'sequence' => $sequence,
            'stage' => BudgetVoteStage::fromResultLabel($resultLabel),
            'date' => $this->dateTime($vote['Data'] ?? null)?->toDateString(),
            'result' => BudgetProposalStatus::fromLabel($resultLabel),
            'result_label' => $resultLabel,
            'subject' => $this->subject($vote),
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

        foreach ($acronyms as $acronym) {
            if (! in_array($acronym, $this->partyOrder, true)) {
                $this->partyOrder[] = $acronym;
            }
        }
    }

    /**
     * An item called up to the plenary is decided there, whatever the committee voted.
     *
     * @param  HasMany<\App\Models\BudgetVote, \Illuminate\Database\Eloquent\Model>  $votes
     */
    private function markFinal(HasMany $votes): void
    {
        $finalStage = (clone $votes)->where('stage', BudgetVoteStage::Plenary)->exists()
            ? BudgetVoteStage::Plenary
            : BudgetVoteStage::Committee;

        (clone $votes)->where('stage', $finalStage)->update(['is_final' => true]);
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
     * @param  array<string, mixed>  $vote
     */
    private function subject(array $vote): ?string
    {
        $subject = ParliamentJson::text($vote['Descricoes']['Descricao'] ?? null);
        $detail = ParliamentJson::text($vote['SubDescricao'] ?? null);

        return ParliamentJson::text(array_filter([$subject, $detail]), ' — ');
    }
}
