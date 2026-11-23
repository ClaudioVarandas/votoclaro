<?php

namespace App\Console\Commands\Concerns;

use App\Models\Budget;
use App\Services\LegislatureContext;
use Illuminate\Database\Eloquent\Collection;

/**
 * The budgets a command works on: the one of --year, or else every budget of the current
 * legislature that has its amendments to import.
 */
trait SelectsBudgets
{
    /**
     * @return Collection<int, Budget>
     */
    private function budgets(LegislatureContext $legislatureContext): Collection
    {
        if ($this->option('year')) {
            return Budget::query()->where('year', (int) $this->option('year'))->get();
        }

        return Budget::query()
            ->where('legislature_id', $legislatureContext->current()?->id)
            ->whereNotNull('proposals_url')
            ->orderBy('year')
            ->get();
    }

    private function noBudgetMessage(): string
    {
        return $this->option('year')
            ? "No {$this->option('year')} budget registered: run ar:budget:register first."
            : 'No budget registered in the current legislature: run ar:budget:register first.';
    }
}
