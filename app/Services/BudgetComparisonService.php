<?php

namespace App\Services;

use App\Enums\BudgetProposalStatus;
use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\BudgetVote;
use Illuminate\Support\Facades\Cache;

/**
 * A budget set against the one of the year before: its totals, each party's amendments, and how
 * fast the amendments passed day by day of the vote.
 */
class BudgetComparisonService
{
    private const CACHE_TTL = 3600;

    /**
     * A day with fewer amendments decided than this, as a lone plenary vote, joins the voting
     * day before it, so the days line up between years.
     */
    private const MIN_DECIDED_PER_DAY = 10;

    public function __construct(private BudgetStatsService $budgetStats) {}

    /**
     * Null when there is no budget of the year before, or it has no amendments yet.
     *
     * @return array{previous: Budget, totals: list<array{key: string, current: int|float, previous: int|float}>, parties: list<array{acronym: string, current: ?array{proposed: int, approved: int, approval_rate: float}, previous: ?array{proposed: int, approved: int, approval_rate: float}}>, pace: list<array{current: ?array{decided: int, approved: int}, previous: ?array{decided: int, approved: int}}>}|null
     */
    public function compare(Budget $budget): ?array
    {
        $previous = Budget::query()
            ->with('legislature')
            ->where('year', $budget->year - 1)
            ->where('kind', $budget->kind)
            ->first();

        if ($previous === null) {
            return null;
        }

        $current = $this->budgetStats->overview($budget);
        $before = $this->budgetStats->overview($previous);

        if ($before['total'] === 0) {
            return null;
        }

        return [
            'previous' => $previous,
            'totals' => $this->totals($current, $before),
            'parties' => $this->parties($budget, $previous),
            'pace' => $this->pace($budget, $previous),
        ];
    }

    /**
     * Against the government only when both legislatures have theirs on record.
     *
     * @param  array{total: int, approved: int, rejected: int, pending: int, voted: int, against_government: ?int}  $current
     * @param  array{total: int, approved: int, rejected: int, pending: int, voted: int, against_government: ?int}  $previous
     * @return list<array{key: string, current: int|float, previous: int|float}>
     */
    private function totals(array $current, array $previous): array
    {
        $rate = fn (array $overview): float => $overview['total'] > 0 ? round($overview['approved'] / $overview['total'] * 100, 1) : 0.0;

        $totals = [
            ['key' => 'total', 'current' => $current['total'], 'previous' => $previous['total']],
            ['key' => 'approved', 'current' => $current['approved'], 'previous' => $previous['approved']],
            ['key' => 'approval_rate', 'current' => $rate($current), 'previous' => $rate($previous)],
            ['key' => 'rejected', 'current' => $current['rejected'], 'previous' => $previous['rejected']],
        ];

        if ($current['against_government'] !== null && $previous['against_government'] !== null) {
            $totals[] = ['key' => 'against_government', 'current' => $current['against_government'], 'previous' => $previous['against_government']];
        }

        return $totals;
    }

    /**
     * The parties that proposed in either year, in this year's order of the Parliament, then the
     * ones that are gone.
     *
     * @return list<array{acronym: string, current: ?array{proposed: int, approved: int, approval_rate: float}, previous: ?array{proposed: int, approved: int, approval_rate: float}}>
     */
    private function parties(Budget $budget, Budget $previous): array
    {
        $pick = fn (array $parties) => collect($parties)
            ->where('proposed', '>', 0)
            ->keyBy('acronym')
            ->map(fn (array $party): array => ['proposed' => $party['proposed'], 'approved' => $party['approved'], 'approval_rate' => $party['approval_rate']]);

        $current = $pick($this->budgetStats->parties($budget));
        $before = $pick($this->budgetStats->parties($previous));

        return $current->keys()
            ->merge($before->keys())
            ->unique()
            ->map(fn (string $acronym): array => [
                'acronym' => $acronym,
                'current' => $current[$acronym] ?? null,
                'previous' => $before[$acronym] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * Day by day of the vote, the amendments decided and approved so far in each year. A year
     * still being voted has fewer days than the other; its missing days are null.
     *
     * @return list<array{current: ?array{decided: int, approved: int}, previous: ?array{decided: int, approved: int}}>
     */
    private function pace(Budget $budget, Budget $previous): array
    {
        $current = $this->cumulativeDays($budget);
        $before = $this->cumulativeDays($previous);
        $days = max(count($current), count($before));

        if ($days === 0) {
            return [];
        }

        return array_map(
            fn (int $day): array => ['current' => $current[$day] ?? null, 'previous' => $before[$day] ?? null],
            range(0, $days - 1),
        );
    }

    /**
     * An amendment counts on the day of its last decisive vote, approved by its outcome.
     *
     * @return list<array{decided: int, approved: int}>
     */
    private function cumulativeDays(Budget $budget): array
    {
        return Cache::remember("budget.{$budget->id}.pace", self::CACHE_TTL, function () use ($budget): array {
            $lastVotes = BudgetVote::query()
                ->join('budget_proposals', 'budget_proposals.id', '=', 'budget_votes.budget_proposal_id')
                ->where('budget_proposals.budget_id', $budget->id)
                ->where('budget_votes.is_final', true)
                ->groupBy('budget_proposals.id', 'budget_proposals.status')
                ->selectRaw('budget_proposals.status, max(budget_votes.date) as decided_on');

            $days = BudgetProposal::query()
                ->fromSub($lastVotes, 'last_votes')
                ->groupBy('decided_on')
                ->orderBy('decided_on')
                ->selectRaw(
                    'decided_on, count(*) as decided, sum(case when status in (?, ?) then 1 else 0 end) as approved',
                    [BudgetProposalStatus::Approved->value, BudgetProposalStatus::PartiallyApproved->value],
                )
                ->toBase()
                ->get();

            $merged = [];
            $carried = ['decided' => 0, 'approved' => 0];

            foreach ($days as $day) {
                $counts = ['decided' => (int) $day->decided + $carried['decided'], 'approved' => (int) $day->approved + $carried['approved']];

                if ($counts['decided'] >= self::MIN_DECIDED_PER_DAY) {
                    $merged[] = $counts;
                    $carried = ['decided' => 0, 'approved' => 0];
                } elseif ($merged !== []) {
                    $merged[array_key_last($merged)]['decided'] += $counts['decided'];
                    $merged[array_key_last($merged)]['approved'] += $counts['approved'];
                    $carried = ['decided' => 0, 'approved' => 0];
                } else {
                    $carried = $counts;
                }
            }

            if ($carried['decided'] > 0) {
                $merged[] = $carried;
            }

            $decided = 0;
            $approved = 0;

            return array_map(function (array $day) use (&$decided, &$approved): array {
                $decided += $day['decided'];
                $approved += $day['approved'];

                return ['decided' => $decided, 'approved' => $approved];
            }, $merged);
        });
    }
}
