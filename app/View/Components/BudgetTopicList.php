<?php

namespace App\View\Components;

use App\Models\Budget;
use App\Models\BudgetTopic;
use App\Services\LegislatureContext;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * One tab of the top of topics on the budget page: the topics in their order, each with the figure
 * the tab ranks them by first.
 */
class BudgetTopicList extends Component
{
    /**
     * @param  Collection<int, BudgetTopic>  $topics
     */
    public function __construct(
        private LegislatureContext $legislatureContext,
        public Budget $budget,
        public Collection $topics,
        public string $rankBy = 'parties',
    ) {}

    public function url(BudgetTopic $topic): string
    {
        return $this->legislatureContext->route(
            'budgets.topics.show',
            ['year' => $this->budget->year, 'topic' => $topic->slug],
            $this->budget->legislature,
        );
    }

    /**
     * The figures under the topic's name, the one the tab ranks by first.
     *
     * @return list<string>
     */
    public function figures(BudgetTopic $topic): array
    {
        $parties = trans_choice('ui.budget.topics.parties_count', $topic->parties_count, ['count' => $topic->parties_count]);
        $proposals = trans_choice('ui.budget.topics.proposals_count', $topic->proposals_count, ['count' => number_format($topic->proposals_count, 0, ',', '.')]);

        return match ($this->rankBy) {
            'proposals' => [$proposals, $parties],
            'divided' => [__('ui.budget.topics.division', ['percent' => $this->divisionPercent($topic)]), $proposals],
            default => [$parties, $proposals],
        };
    }

    public function divisionPercent(BudgetTopic $topic): int
    {
        return (int) round(($topic->division ?? 0) * 100);
    }

    public function showsDivision(): bool
    {
        return $this->rankBy === 'divided';
    }

    public function render(): View|Closure|string
    {
        return view('components.budget-topic-list');
    }
}
