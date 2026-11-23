<?php

namespace App\View\Components;

use App\Enums\BudgetVotePosition;
use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Services\BudgetMatrixService;
use App\Services\LegislatureContext;
use Closure;
use Countable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The grid of amendments (rows) by party (columns), each cell the party's position in the vote
 * that decided the amendment.
 */
class BudgetVoteMatrix extends Component
{
    /**
     * @param  iterable<BudgetProposal>  $proposals
     * @param  list<string>  $parties
     * @param  array<int, array<string, string>>  $cells
     * @param  array<string, array<string, int>>  $totals
     */
    public function __construct(
        private LegislatureContext $legislatureContext,
        public Budget $budget,
        public iterable $proposals,
        public array $parties,
        public array $cells,
        public array $totals = [],
    ) {}

    public function cell(BudgetProposal $proposal, string $party): ?string
    {
        return $this->cells[$proposal->id][$party] ?? null;
    }

    public function symbol(?string $cell): string
    {
        return match (true) {
            $cell === null => '',
            $cell === BudgetMatrixService::SPLIT => '±',
            default => BudgetVotePosition::from($cell)->symbol(),
        };
    }

    public function label(?string $cell): string
    {
        return match (true) {
            $cell === null => __('ui.budget.position.none'),
            $cell === BudgetMatrixService::SPLIT => __('ui.budget.position.split'),
            default => BudgetVotePosition::from($cell)->label(),
        };
    }

    public function cellClasses(?string $cell): string
    {
        return match ($cell) {
            BudgetVotePosition::Favor->value => 'bg-republic-100 text-republic-800 dark:bg-republic-950 dark:text-republic-300',
            BudgetVotePosition::Contra->value => 'bg-parliament-100 text-parliament-800 dark:bg-parliament-950 dark:text-parliament-300',
            BudgetVotePosition::Abstencao->value => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
            BudgetMatrixService::SPLIT => 'bg-sand-200 text-sand-700 dark:bg-sand-800 dark:text-sand-300',
            default => 'text-sand-400 dark:text-sand-600',
        };
    }

    /**
     * The rows of the totals, one per position, styled like the cells they count.
     *
     * @return array<string, array{label: string, symbol: string, classes: string}>
     */
    public function totalRows(): array
    {
        return collect([BudgetVotePosition::Favor, BudgetVotePosition::Contra, BudgetVotePosition::Abstencao])
            ->mapWithKeys(fn (BudgetVotePosition $position): array => [$position->value => [
                'label' => $position->label(),
                'symbol' => $position->symbol(),
                'classes' => $this->cellClasses($position->value),
            ]])
            ->all();
    }

    /**
     * How many amendments the totals count: all the filtered ones, not only this page.
     */
    public function totalProposals(): int
    {
        return match (true) {
            $this->proposals instanceof LengthAwarePaginator => $this->proposals->total(),
            $this->proposals instanceof Countable => count($this->proposals),
            default => iterator_count((fn (): iterable => yield from $this->proposals)()),
        };
    }

    public function proposalUrl(BudgetProposal $proposal): string
    {
        return $this->legislatureContext->route(
            'budgets.proposals.show',
            ['year' => $this->budget->year, 'proposal' => $proposal->slug],
            $this->budget->legislature,
        );
    }

    /**
     * The proposing parties, or the proposer as the Parliament names it when no party matched.
     */
    public function proposer(BudgetProposal $proposal): string
    {
        return $proposal->parties->isNotEmpty()
            ? $proposal->parties->pluck('acronym')->implode(', ')
            : (string) $proposal->proposer_label;
    }

    public function render(): View|Closure|string
    {
        return view('components.budget-vote-matrix');
    }
}
