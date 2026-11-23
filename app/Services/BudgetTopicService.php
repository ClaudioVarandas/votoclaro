<?php

namespace App\Services;

use App\Enums\BudgetVotePosition;
use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\BudgetTopic;
use Illuminate\Support\Facades\DB;

/**
 * Finds the topics of a budget's amendments again, with how many parties proposed on each and how
 * divided the votes on them were. Run after every import, as new amendments and votes come in.
 */
class BudgetTopicService
{
    /** A topic needs this many voted amendments before its division means anything. */
    private const MIN_VOTED_FOR_DIVISION = 3;

    /** A vote needs this many parties with a position to say whether it divided them. */
    private const MIN_PARTIES_FOR_DIVISION = 3;

    public function __construct(private BudgetTopicExtractor $extractor, private BudgetMatrixService $matrix) {}

    /**
     * @return int the number of topics found
     */
    public function rebuild(Budget $budget): int
    {
        $proposals = BudgetProposal::query()
            ->where('budget_id', $budget->id)
            ->with('parties:id,acronym')
            ->get(['id', 'title'])
            ->keyBy('id');

        $topics = $this->extractor->extract($proposals->map(fn (BudgetProposal $proposal): string => $proposal->subject()));
        $cells = $this->matrix->cells(BudgetProposal::query()->where('budget_id', $budget->id));

        DB::transaction(function () use ($budget, $topics, $proposals, $cells): void {
            $budget->topics()->delete();

            foreach ($topics as $found) {
                $ids = $found['proposal_ids'];
                $divisions = array_values(array_filter(array_map(fn (int $id): ?float => $this->division($cells[$id] ?? []), $ids), fn (?float $division): bool => $division !== null));

                $topic = BudgetTopic::create([
                    'budget_id' => $budget->id,
                    'slug' => $found['slug'],
                    'label' => $found['label'],
                    'proposals_count' => count($ids),
                    'parties_count' => collect($ids)->flatMap(fn (int $id) => $proposals[$id]->parties->pluck('acronym'))->unique()->count(),
                    'voted_count' => count(array_filter($ids, fn (int $id): bool => isset($cells[$id]))),
                    'division' => count($divisions) >= self::MIN_VOTED_FOR_DIVISION ? round(array_sum($divisions) / count($divisions), 4) : null,
                ]);

                foreach (array_chunk($ids, 500) as $chunk) {
                    DB::table('budget_proposal_budget_topic')->insert(array_map(
                        fn (int $id): array => ['budget_topic_id' => $topic->id, 'budget_proposal_id' => $id],
                        $chunk,
                    ));
                }
            }
        });

        return count($topics);
    }

    /**
     * How much a vote divided the parties: 0 when they all voted alike, 1 when as many voted for
     * as against. Abstentions soften it; a party that split its vote is left out.
     *
     * @param  array<string, string>  $positions  by party
     */
    public function division(array $positions): ?float
    {
        $counts = array_count_values($positions);
        $favor = $counts[BudgetVotePosition::Favor->value] ?? 0;
        $contra = $counts[BudgetVotePosition::Contra->value] ?? 0;
        $total = $favor + $contra + ($counts[BudgetVotePosition::Abstencao->value] ?? 0);

        if ($total < self::MIN_PARTIES_FOR_DIVISION) {
            return null;
        }

        return 2 * min($favor, $contra) / $total;
    }
}
