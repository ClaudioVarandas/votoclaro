<?php

namespace App\Services;

use App\Enums\BudgetProposalStatus;
use App\Enums\BudgetVotePosition;
use App\Models\Budget;
use App\Models\BudgetArticle;
use App\Models\BudgetProposal;
use App\Models\BudgetTheme;
use App\Models\BudgetTopic;
use App\Models\BudgetVotePosition as Position;
use App\Models\Party;
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

    public function __construct(private BudgetMatrixService $matrix) {}

    /**
     * @return array{total: int, approved: int, rejected: int, pending: int, voted: int}
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
            ];
        });
    }

    /**
     * Per party, in the Parliament's order: the amendments it proposed, how many passed, and how
     * often it voted against the amendments of other parties in the vote that decided them.
     *
     * @return list<array{acronym: string, proposed: int, approved: int, approval_rate: float, others_voted: int, against_others: int, against_rate: float, articles_voted: int, articles: array{favor: int, contra: int, abstencao: int}, articles_favor_rate: float}>
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

            return $this->partyOrder($budget, $proposed->keys())
                ->map(function (string $acronym) use ($proposed, $onOthers, $articleSupport): array {
                    $count = (int) ($proposed[$acronym]->proposed ?? 0);
                    $approved = (int) ($proposed[$acronym]->approved ?? 0);
                    $othersVoted = (int) ($onOthers[$acronym]->voted ?? 0);
                    $againstOthers = (int) ($onOthers[$acronym]->against ?? 0);

                    return [
                        'acronym' => $acronym,
                        'proposed' => $count,
                        'approved' => $approved,
                        'approval_rate' => $count > 0 ? round($approved / $count * 100, 1) : 0.0,
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
     * @return Collection<int, BudgetTheme> each with proposals_count
     */
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
}
