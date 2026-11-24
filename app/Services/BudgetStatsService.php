<?php

namespace App\Services;

use App\Enums\BudgetProposalStatus;
use App\Enums\BudgetVotePosition;
use App\Enums\VoteResult;
use App\Models\Budget;
use App\Models\BudgetArticle;
use App\Models\BudgetProposal;
use App\Models\BudgetTheme;
use App\Models\BudgetTopic;
use App\Models\BudgetVote;
use App\Models\BudgetVotePosition as Position;
use App\Models\Legislature;
use App\Models\Party;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The figures of a State Budget's amendments. They change only when the import runs, which clears
 * the cache.
 */
class BudgetStatsService
{
    private const CACHE_TTL = 3600;

    /** The topics ranked on the budget page are the ones with most amendments. */
    private const TOPIC_POOL = 40;

    private const TOPICS_LISTED = 12;

    /** A topic is ranked by division once at least this many of its amendments were voted. */
    private const MIN_VOTED_FOR_DIVISION = 5;

    private const MOST_AMENDED_LISTED = 6;

    /** A pair of parties gets an agreement rate once both voted at least this many amendments. */
    private const MIN_VOTED_FOR_AGREEMENT = 20;

    /** The rows and party columns of the sample of the vote grid on the home page. */
    private const HIGHLIGHT_ROWS = 6;

    private const HIGHLIGHT_PARTIES = 9;

    private const HIGHLIGHT_TOPICS = 3;

    public function __construct(private BudgetMatrixService $matrix) {}

    /**
     * The totals of a budget. Against the government is null when its legislature has no
     * government recorded.
     *
     * @return array{total: int, approved: int, rejected: int, pending: int, voted: int, against_government: ?int}
     */
    public function overview(Budget $budget): array
    {
        return Cache::remember("budget.{$budget->id}.overview", self::CACHE_TTL, function () use ($budget): array {
            $counts = BudgetProposal::query()
                ->where('budget_id', $budget->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->toBase()
                ->pluck('total', 'status');

            return [
                'total' => (int) $counts->sum(),
                'approved' => (int) ($counts[BudgetProposalStatus::Approved->value] ?? 0) + (int) ($counts[BudgetProposalStatus::PartiallyApproved->value] ?? 0),
                'rejected' => (int) ($counts[BudgetProposalStatus::Rejected->value] ?? 0),
                'pending' => (int) ($counts[BudgetProposalStatus::Pending->value] ?? 0),
                'voted' => BudgetProposal::query()->where('budget_id', $budget->id)->whereHas('finalVotes')->count(),
                'against_government' => $this->governmentOf($budget) === []
                    ? null
                    : $this->againstGovernment($budget)->count(),
            ];
        });
    }

    /**
     * Per party, in the Parliament's order: the amendments it proposed, how many passed, and how
     * often it voted against the amendments of other parties in the vote that decided them.
     *
     * @return list<array{acronym: string, proposed: int, approved: int, approval_rate: float, approved_against_government: int, others_voted: int, against_others: int, against_rate: float, articles_voted: int, articles: array{favor: int, contra: int, abstencao: int}, articles_favor_rate: float}>
     */
    public function parties(Budget $budget): array
    {
        return Cache::remember("budget.{$budget->id}.parties", self::CACHE_TTL, function () use ($budget): array {
            $proposed = Party::query()
                ->join('budget_proposal_party', 'budget_proposal_party.party_id', '=', 'parties.id')
                ->join('budget_proposals', 'budget_proposals.id', '=', 'budget_proposal_party.budget_proposal_id')
                ->where('budget_proposals.budget_id', $budget->id)
                ->groupBy('parties.acronym')
                ->selectRaw(
                    'parties.acronym, count(*) as proposed, sum(case when budget_proposals.status in (?, ?) then 1 else 0 end) as approved',
                    [BudgetProposalStatus::Approved->value, BudgetProposalStatus::PartiallyApproved->value],
                )
                ->toBase()
                ->get()
                ->keyBy('acronym');

            $onOthers = Position::query()
                ->join('budget_votes', 'budget_votes.id', '=', 'budget_vote_positions.budget_vote_id')
                ->join('budget_proposals', 'budget_proposals.id', '=', 'budget_votes.budget_proposal_id')
                ->join('parties', 'parties.acronym', '=', 'budget_vote_positions.party')
                ->where('budget_proposals.budget_id', $budget->id)
                ->where('budget_votes.is_final', true)
                ->whereNull('budget_vote_positions.deputy')
                ->whereNotExists(fn (QueryBuilder $query) => $query
                    ->selectRaw('1')
                    ->from('budget_proposal_party')
                    ->whereColumn('budget_proposal_party.budget_proposal_id', 'budget_proposals.id')
                    ->whereColumn('budget_proposal_party.party_id', 'parties.id'))
                ->groupBy('budget_vote_positions.party')
                ->selectRaw(
                    'budget_vote_positions.party, count(distinct budget_proposals.id) as voted, count(distinct case when budget_vote_positions.position = ? then budget_proposals.id end) as against',
                    [BudgetVotePosition::Contra->value],
                )
                ->toBase()
                ->get()
                ->keyBy('party');

            $articleSupport = $this->articleSupport($budget);

            $againstGovernment = $this->governmentOf($budget) === []
                ? collect()
                : Party::query()
                    ->join('budget_proposal_party', 'budget_proposal_party.party_id', '=', 'parties.id')
                    ->whereIn('budget_proposal_party.budget_proposal_id', $this->againstGovernment($budget)->select('budget_proposals.id'))
                    ->groupBy('parties.acronym')
                    ->selectRaw('parties.acronym, count(*) as total')
                    ->toBase()
                    ->pluck('total', 'acronym');

            return $this->partyOrder($budget, $proposed->keys())
                ->map(function (string $acronym) use ($proposed, $onOthers, $articleSupport, $againstGovernment): array {
                    $count = (int) ($proposed[$acronym]->proposed ?? 0);
                    $approved = (int) ($proposed[$acronym]->approved ?? 0);
                    $othersVoted = (int) ($onOthers[$acronym]->voted ?? 0);
                    $againstOthers = (int) ($onOthers[$acronym]->against ?? 0);

                    return [
                        'acronym' => $acronym,
                        'proposed' => $count,
                        'approved' => $approved,
                        'approval_rate' => $count > 0 ? round($approved / $count * 100, 1) : 0.0,
                        'approved_against_government' => (int) ($againstGovernment[$acronym] ?? 0),
                        'others_voted' => $othersVoted,
                        'against_others' => $againstOthers,
                        'against_rate' => $othersVoted > 0 ? round($againstOthers / $othersVoted * 100, 1) : 0.0,
                        'articles_voted' => $articleSupport[$acronym]['voted'] ?? 0,
                        'articles' => [
                            'favor' => $articleSupport[$acronym]['favor'] ?? 0,
                            'contra' => $articleSupport[$acronym]['contra'] ?? 0,
                            'abstencao' => $articleSupport[$acronym]['abstencao'] ?? 0,
                        ],
                        'articles_favor_rate' => $articleSupport[$acronym]['rate'] ?? 0.0,
                    ];
                })
                ->values()
                ->all();
        });
    }

    /**
     * How often each pair of parties voted the same way, over the amendments both voted in the
     * vote that decided them. An absence or a split vote (the amendment voted in parts and the
     * party not voting them all alike) leaves the amendment out for that party. The rate is null
     * when a pair shared too few votes to say much, as a single-deputy group outside the committee.
     *
     * @return array{parties: list<string>, pairs: array<string, array<string, array{voted: int, same: int, rate: ?float}>>}
     */
    public function agreement(Budget $budget): array
    {
        return Cache::remember("budget.{$budget->id}.agreement", self::CACHE_TTL, function () use ($budget): array {
            $cells = $this->matrix->cells(BudgetProposal::query()->where('budget_id', $budget->id));
            $parties = $this->matrix->votingParties($cells, $this->partyOrder($budget)->all());
            $counted = [BudgetVotePosition::Favor->value, BudgetVotePosition::Contra->value, BudgetVotePosition::Abstencao->value];
            $pairs = [];

            foreach ($parties as $first) {
                foreach ($parties as $second) {
                    $pairs[$first][$second] = ['voted' => 0, 'same' => 0];
                }
            }

            foreach ($cells as $row) {
                $positions = array_filter($row, fn (string $position): bool => in_array($position, $counted, true));

                foreach ($positions as $first => $position) {
                    foreach ($positions as $second => $other) {
                        if (isset($pairs[$first][$second])) {
                            $pairs[$first][$second]['voted']++;
                            $pairs[$first][$second]['same'] += (int) ($position === $other);
                        }
                    }
                }
            }

            return [
                'parties' => $parties,
                'pairs' => array_map(fn (array $row): array => array_map(fn (array $pair): array => [
                    ...$pair,
                    'rate' => $pair['voted'] >= self::MIN_VOTED_FOR_AGREEMENT ? round($pair['same'] / $pair['voted'] * 100, 1) : null,
                ], $row), $pairs),
            ];
        });
    }

    /**
     * @return Collection<int, BudgetTheme> each with proposals_count
     */
    public const STAGE_SUBMITTED = 'submitted';

    public const STAGE_VOTING = 'voting';

    public const STAGE_CLOSED = 'closed';

    /**
     * The budget the home page puts first: the latest one still open, or else the latest. With
     * the stage it is at, its figures, its first topics, and a sample of its vote grid (the
     * amendments voted last).
     *
     * @return array{budget: Budget, stage: string, final_vote: ?array{approved: bool, date: ?\Illuminate\Support\Carbon}, overview: array{total: int, approved: int, rejected: int, pending: int, voted: int, against_government: ?int}, government: list<string>, topics: Collection<int, BudgetTopic>, sample: array{parties: list<string>, rows: list<list<?string>>}}|null
     */
    public function highlight(Legislature $legislature): ?array
    {
        $budgets = Budget::query()->with('initiative')->where('legislature_id', $legislature->id)->orderByDesc('year')->get();

        if ($budgets->isEmpty()) {
            return null;
        }

        $stages = $budgets->mapWithKeys(fn (Budget $budget): array => [$budget->id => $this->stage($budget)]);
        $budget = $budgets->first(fn (Budget $budget): bool => $stages[$budget->id] !== self::STAGE_CLOSED) ?? $budgets->first();
        $finalVote = $budget->initiative?->finalGlobalVote();

        return [
            'budget' => $budget,
            'stage' => $stages[$budget->id],
            'final_vote' => $finalVote ? ['approved' => $finalVote->result === VoteResult::Aprovado, 'date' => $finalVote->date] : null,
            'overview' => $this->overview($budget),
            'government' => $this->governmentOf($budget),
            'topics' => $this->topics($budget)['parties']->take(self::HIGHLIGHT_TOPICS)->values(),
            'sample' => Cache::remember("budget.{$budget->id}.highlight_sample", self::CACHE_TTL, fn (): array => $this->voteSample($budget)),
        ];
    }

    /**
     * Where a budget stands: its amendments in but none voted yet; being voted; or closed, once
     * the bill had its final global vote. Without that vote on record, as before the initiatives
     * import brings it, a budget with nothing left to vote counts as closed too.
     */
    public function stage(Budget $budget): string
    {
        $overview = $this->overview($budget);

        return match (true) {
            $budget->initiative?->finalGlobalVote() !== null => self::STAGE_CLOSED,
            $overview['voted'] === 0 => self::STAGE_SUBMITTED,
            $overview['pending'] > 0 => self::STAGE_VOTING,
            default => self::STAGE_CLOSED,
        };
    }

    /**
     * The decisive positions on the amendments voted last, one row each, in the Parliament's
     * order of the parties.
     *
     * @return array{parties: list<string>, rows: list<list<?string>>}
     */
    private function voteSample(Budget $budget): array
    {
        $ids = BudgetVote::query()
            ->join('budget_proposals', 'budget_proposals.id', '=', 'budget_votes.budget_proposal_id')
            ->where('budget_proposals.budget_id', $budget->id)
            ->where('budget_votes.is_final', true)
            ->groupBy('budget_votes.budget_proposal_id')
            ->orderByRaw('max(budget_votes.date) desc')
            ->orderByDesc('budget_votes.budget_proposal_id')
            ->limit(self::HIGHLIGHT_ROWS)
            ->pluck('budget_votes.budget_proposal_id');

        $cells = $this->matrix->cells(BudgetProposal::query()->whereIn('id', $ids));
        $parties = array_slice($this->matrix->votingParties($cells, $this->partyOrder($budget)->all()), 0, self::HIGHLIGHT_PARTIES);

        return [
            'parties' => $parties,
            'rows' => $ids->map(fn (int $id): array => array_map(fn (string $party): ?string => $cells[$id][$party] ?? null, $parties))->values()->all(),
        ];
    }

    /**
     * The Government's bill in figures: its articles, how many were approved and voted, and the
     * ones the parties tried most to change.
     *
     * @return array{total: int, approved: int, pending: int, voted: int, most_amended: Collection<int, BudgetArticle>}
     */
    public function articles(Budget $budget): array
    {
        return Cache::remember("budget.{$budget->id}.articles", self::CACHE_TTL, function () use ($budget): array {
            $statuses = BudgetArticle::query()
                ->where('budget_id', $budget->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->toBase()
                ->pluck('total', 'status');

            return [
                'total' => (int) $statuses->sum(),
                'approved' => (int) ($statuses[BudgetProposalStatus::Approved->value] ?? 0) + (int) ($statuses[BudgetProposalStatus::PartiallyApproved->value] ?? 0),
                'pending' => (int) ($statuses[BudgetProposalStatus::Pending->value] ?? 0),
                'voted' => BudgetArticle::query()->where('budget_id', $budget->id)->whereHas('finalVotes')->count(),
                'most_amended' => BudgetArticle::query()
                    ->where('budget_id', $budget->id)
                    ->whereHas('proposals')
                    ->withCount('proposals')
                    ->orderByDesc('proposals_count')
                    ->orderBy('position')
                    ->limit(self::MOST_AMENDED_LISTED)
                    ->get(),
            ];
        });
    }

    /**
     * How each party voted the articles of the Government's bill, in the vote that decided each.
     * An article voted in parts that the party did not vote all alike counts as split.
     *
     * @return array<string, array{voted: int, favor: int, contra: int, abstencao: int, rate: float}>
     */
    private function articleSupport(Budget $budget): array
    {
        $support = [];

        foreach ($this->matrix->cells(BudgetArticle::query()->where('budget_id', $budget->id)) as $positions) {
            foreach ($positions as $party => $position) {
                $support[$party] ??= ['voted' => 0, 'favor' => 0, 'contra' => 0, 'abstencao' => 0];
                $support[$party]['voted']++;

                if (isset($support[$party][$position])) {
                    $support[$party][$position]++;
                }
            }
        }

        return array_map(fn (array $counts): array => [...$counts, 'rate' => round($counts['favor'] / $counts['voted'] * 100, 1)], $support);
    }

    /**
     * The top of topics three ways, among the topics with most amendments that more than one party
     * proposed on: the ones most parties proposed on, the ones with most amendments, and the ones
     * whose votes divided the parties most.
     *
     * @return array{parties: Collection<int, BudgetTopic>, proposals: Collection<int, BudgetTopic>, divided: Collection<int, BudgetTopic>}
     */
    public function topics(Budget $budget): array
    {
        return Cache::remember("budget.{$budget->id}.topics", self::CACHE_TTL, function () use ($budget): array {
            $pool = $budget->topics()
                ->where('parties_count', '>', 1)
                ->orderByDesc('proposals_count')
                ->orderBy('label')
                ->limit(self::TOPIC_POOL)
                ->get();

            return [
                'parties' => $pool->sortBy([['parties_count', 'desc'], ['proposals_count', 'desc']])->take(self::TOPICS_LISTED)->values(),
                'proposals' => $pool->take(self::TOPICS_LISTED)->values(),
                'divided' => $pool
                    ->filter(fn (BudgetTopic $topic): bool => $topic->division !== null && $topic->voted_count >= self::MIN_VOTED_FOR_DIVISION)
                    ->sortByDesc('division')
                    ->take(self::TOPICS_LISTED)
                    ->values(),
            ];
        });
    }

    public function themes(Budget $budget): Collection
    {
        return Cache::remember("budget.{$budget->id}.themes", self::CACHE_TTL, fn (): Collection => BudgetTheme::query()
            ->withCount(['proposals' => fn ($query) => $query->where('budget_id', $budget->id)])
            ->get()
            ->filter(fn (BudgetTheme $theme): bool => $theme->proposals_count > 0)
            ->sortByDesc('proposals_count')
            ->values());
    }

    /**
     * The parties that voted the budget, in the Parliament's order, then any that only proposed.
     *
     * @param  Collection<int, string>  $proposingAcronyms
     * @return Collection<int, string>
     */
    public function partyOrder(Budget $budget, ?Collection $proposingAcronyms = null): Collection
    {
        return collect($budget->party_order ?? [])
            ->merge($proposingAcronyms ?? [])
            ->unique()
            ->values();
    }

    /**
     * The acronyms of the parties in government in the budget's legislature, in the order the
     * Parliament lists them (the largest first).
     *
     * @return list<string>
     */
    public function governmentOf(Budget $budget): array
    {
        $government = $budget->legislature?->governmentAcronyms() ?? [];

        return $this->partyOrder($budget, collect($government))->intersect($government)->values()->all();
    }

    /**
     * @return EloquentBuilder<BudgetProposal>
     */
    private function againstGovernment(Budget $budget): EloquentBuilder
    {
        return BudgetProposal::query()->where('budget_id', $budget->id)->approvedAgainstGovernment($this->governmentOf($budget));
    }
}
