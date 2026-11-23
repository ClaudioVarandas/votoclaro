<?php

namespace App\Services;

use App\Enums\BudgetProposalStatus;
use App\Enums\BudgetVotePosition;
use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\BudgetTheme;
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
     * @return list<array{acronym: string, proposed: int, approved: int, approval_rate: float, others_voted: int, against_others: int}>
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

            return $this->partyOrder($budget, $proposed->keys())
                ->map(function (string $acronym) use ($proposed, $onOthers): array {
                    $count = (int) ($proposed[$acronym]->proposed ?? 0);
                    $approved = (int) ($proposed[$acronym]->approved ?? 0);

                    return [
                        'acronym' => $acronym,
                        'proposed' => $count,
                        'approved' => $approved,
                        'approval_rate' => $count > 0 ? round($approved / $count * 100, 1) : 0.0,
                        'others_voted' => (int) ($onOthers[$acronym]->voted ?? 0),
                        'against_others' => (int) ($onOthers[$acronym]->against ?? 0),
                    ];
                })
                ->values()
                ->all();
        });
    }

    /**
     * @return Collection<int, BudgetTheme> each with proposals_count
     */
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
