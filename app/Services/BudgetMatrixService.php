<?php

namespace App\Services;

use App\Models\BudgetProposal;
use App\Models\BudgetVotePosition;
use Illuminate\Database\Eloquent\Builder;

/**
 * How each party voted each amendment, as one cell per party, from the vote that decided it.
 */
class BudgetMatrixService
{
    public const SPLIT = 'split';

    /**
     * The cell of a party is its position; "split" when the amendment was voted in parts and
     * the party did not vote them all the same way; missing when it did not vote, as single-deputy
     * groups outside the committee.
     *
     * @param  Builder<BudgetProposal>  $proposals
     * @return array<int, array<string, string>> by amendment id, then by party acronym
     */
    public function cells(Builder $proposals): array
    {
        $rows = BudgetVotePosition::query()
            ->join('budget_votes', 'budget_votes.id', '=', 'budget_vote_positions.budget_vote_id')
            ->where('budget_votes.is_final', true)
            ->whereNull('budget_vote_positions.deputy')
            ->whereNotNull('budget_vote_positions.party')
            ->whereIn('budget_votes.budget_proposal_id', (clone $proposals)->reorder()->select('budget_proposals.id'))
            ->toBase()
            ->get(['budget_votes.budget_proposal_id', 'budget_vote_positions.party', 'budget_vote_positions.position']);

        $cells = [];

        foreach ($rows as $row) {
            $current = $cells[$row->budget_proposal_id][$row->party] ?? null;
            $cells[$row->budget_proposal_id][$row->party] = $current === null || $current === $row->position ? $row->position : self::SPLIT;
        }

        return $cells;
    }

    /**
     * The parties that voted any of these amendments, in the given order. A group without a seat
     * in the budget committee only votes the few called up to the plenary, and an empty column
     * for it would only take room.
     *
     * @param  array<int, array<string, string>>  $cells
     * @param  list<string>  $order
     * @return list<string>
     */
    public function votingParties(array $cells, array $order): array
    {
        $voted = [];

        foreach ($cells as $row) {
            $voted += array_fill_keys(array_keys($row), true);
        }

        return array_values(array_filter($order, fn (string $party): bool => isset($voted[$party])));
    }

    /**
     * @param  array<int, array<string, string>>  $cells
     * @param  iterable<string>  $parties
     * @return array<string, array<string, int>> by party, then by position
     */
    public function totals(array $cells, iterable $parties): array
    {
        $totals = [];

        foreach ($parties as $party) {
            $totals[$party] = ['favor' => 0, 'contra' => 0, 'abstencao' => 0];

            foreach ($cells as $row) {
                if (isset($row[$party], $totals[$party][$row[$party]])) {
                    $totals[$party][$row[$party]]++;
                }
            }
        }

        return $totals;
    }
}
