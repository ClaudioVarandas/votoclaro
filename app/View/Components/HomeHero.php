<?php

namespace App\View\Components;

use App\Enums\BudgetVotePosition;
use App\Models\Budget;
use App\Models\BudgetTopic;
use App\Services\BudgetMatrixService;
use App\Services\BudgetStatsService;
use App\Services\LegislatureContext;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The opening of the home page: the State Budget at the stage it is at (amendments in, being
 * voted, closed), with a sample of its real vote grid as the picture; a plain introduction when
 * the legislature has no budget yet.
 */
class HomeHero extends Component
{
    /**
     * @param  array{budget: Budget, stage: string, final_vote: ?array{approved: bool, date: ?\Illuminate\Support\Carbon}, overview: array<string, ?int>, government?: list<string>, topics: iterable<BudgetTopic>, sample: array{parties: list<string>, rows: list<list<?string>>}}|null  $highlight
     */
    public function __construct(private LegislatureContext $legislatureContext, public ?array $highlight = null) {}

    /** Columns of 2rem for the sample grid, one per party, written out so the stylesheet keeps them. */
    private const SAMPLE_COLUMNS = [
        1 => 'grid-cols-[repeat(1,2rem)]',
        2 => 'grid-cols-[repeat(2,2rem)]',
        3 => 'grid-cols-[repeat(3,2rem)]',
        4 => 'grid-cols-[repeat(4,2rem)]',
        5 => 'grid-cols-[repeat(5,2rem)]',
        6 => 'grid-cols-[repeat(6,2rem)]',
        7 => 'grid-cols-[repeat(7,2rem)]',
        8 => 'grid-cols-[repeat(8,2rem)]',
        9 => 'grid-cols-[repeat(9,2rem)]',
    ];

    public function sampleColumnsClass(): string
    {
        return self::SAMPLE_COLUMNS[max(1, min(9, count($this->highlight['sample']['parties'] ?? [])))];
    }

    public function hasBudget(): bool
    {
        return $this->highlight !== null;
    }

    public function isSubmitted(): bool
    {
        return ($this->highlight['stage'] ?? null) === BudgetStatsService::STAGE_SUBMITTED;
    }

    public function isVoting(): bool
    {
        return ($this->highlight['stage'] ?? null) === BudgetStatsService::STAGE_VOTING;
    }

    public function isClosed(): bool
    {
        return ($this->highlight['stage'] ?? null) === BudgetStatsService::STAGE_CLOSED;
    }

    public function badge(): string
    {
        return __('ui.home.badge.'.$this->highlight['stage']);
    }

    public function title(): string
    {
        return __('ui.home.title.'.$this->highlight['stage'], ['year' => $this->highlight['budget']->year]);
    }

    /**
     * The sentence under the title. Once closed, it opens with the bill's final global vote when
     * it is on record.
     */
    public function lead(): string
    {
        $overview = $this->highlight['overview'];
        $figures = ['count' => $this->figure($overview['total']), 'approved' => $this->figure($overview['approved'])];

        if (! $this->isClosed()) {
            return __('ui.home.lead.'.$this->highlight['stage'], $figures);
        }

        $finalVote = $this->highlight['final_vote'];
        $outcome = $finalVote === null ? '' : __($finalVote['approved'] ? 'ui.home.final_approved' : 'ui.home.final_rejected', [
            'date' => $finalVote['date']?->format('d/m/Y') ?? '',
        ]).' ';

        return $outcome.__('ui.home.lead.closed', $figures);
    }

    /**
     * The figures shown under the lead, by overview key: what is still to vote only matters while
     * voting, and before any vote only the number of amendments says something.
     *
     * @return array<string, string> label by overview key
     */
    public function figures(): array
    {
        return match (true) {
            $this->isSubmitted() => ['total' => __('ui.budget.overview.proposals')],
            $this->isVoting() => ['approved' => __('ui.budget.overview.approved'), 'rejected' => __('ui.budget.overview.rejected'), 'pending' => __('ui.budget.overview.pending')],
            default => ['approved' => __('ui.budget.overview.approved'), 'rejected' => __('ui.budget.overview.rejected')],
        };
    }

    public function figureClasses(string $key): string
    {
        return match ($key) {
            'approved' => 'text-republic-700 dark:text-republic-400',
            'rejected' => 'text-parliament-700 dark:text-parliament-400',
            'pending' => 'text-amber-700 dark:text-amber-400',
            default => 'text-sand-900 dark:text-sand-100',
        };
    }

    public function budgetUrl(): string
    {
        return $this->legislatureContext->route('budgets.show', ['year' => $this->highlight['budget']->year], $this->highlight['budget']->legislature);
    }

    public function matrixUrl(): string
    {
        return $this->legislatureContext->route('budgets.proposals.index', ['year' => $this->highlight['budget']->year], $this->highlight['budget']->legislature);
    }

    /**
     * How many amendments passed against the vote of every government party, once any vote is in
     * and the legislature has its government recorded.
     */
    public function againstGovernment(): ?string
    {
        $count = $this->highlight['overview']['against_government'] ?? null;

        if ($this->isSubmitted() || ! $count) {
            return null;
        }

        return trans_choice('ui.home.against_government', $count, [
            'count' => $this->figure($count),
            'parties' => implode(', ', $this->highlight['government'] ?? []),
        ]);
    }

    public function againstGovernmentUrl(): string
    {
        return $this->legislatureContext->route('budgets.proposals.index', ['year' => $this->highlight['budget']->year, 'contra_governo' => 1], $this->highlight['budget']->legislature);
    }

    public function topicUrl(BudgetTopic $topic): string
    {
        return $this->legislatureContext->route('budgets.topics.show', ['year' => $this->highlight['budget']->year, 'topic' => $topic->slug], $this->highlight['budget']->legislature);
    }

    /**
     * The share of the amendments already voted, from 0 to 1.
     */
    public function votedShare(): float
    {
        $total = $this->highlight['overview']['total'] ?? 0;

        return $total > 0 ? $this->highlight['overview']['voted'] / $total : 0.0;
    }

    public function showsSample(): bool
    {
        return $this->hasBudget() && $this->highlight['sample']['rows'] !== [] && $this->highlight['sample']['parties'] !== [];
    }

    public function figure(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    public function symbol(?string $position): string
    {
        return match (true) {
            $position === null => '',
            $position === BudgetMatrixService::SPLIT => '±',
            default => BudgetVotePosition::from($position)->symbol(),
        };
    }

    /**
     * The sample cells, colored like the vote grid they come from.
     */
    public function cellClasses(?string $position): string
    {
        return match ($position) {
            BudgetVotePosition::Favor->value => 'bg-republic-100 text-republic-800 dark:bg-republic-950 dark:text-republic-300',
            BudgetVotePosition::Contra->value => 'bg-parliament-100 text-parliament-800 dark:bg-parliament-950 dark:text-parliament-300',
            BudgetVotePosition::Abstencao->value => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
            BudgetMatrixService::SPLIT => 'bg-sand-200 text-sand-700 dark:bg-sand-800 dark:text-sand-300',
            default => 'bg-sand-50 dark:bg-sand-900',
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.home-hero');
    }
}
