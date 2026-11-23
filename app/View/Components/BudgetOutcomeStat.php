<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A budget total with its icon and color: a large card on the budget page, a compact tile in the
 * list of budgets.
 */
class BudgetOutcomeStat extends Component
{
    public function __construct(
        public string $outcome,
        public string $label,
        public int $value,
        public bool $compact = false,
    ) {}

    public function formattedValue(): string
    {
        return number_format($this->value, 0, ',', '.');
    }

    /**
     * Nothing left to vote is not a warning, so a zero keeps the plain color.
     */
    public function valueClasses(): string
    {
        if ($this->value === 0) {
            return 'text-sand-900 dark:text-sand-100';
        }

        return match ($this->outcome) {
            'approved' => 'text-republic-700 dark:text-republic-400',
            'rejected' => 'text-parliament-700 dark:text-parliament-400',
            'pending' => 'text-amber-700 dark:text-amber-400',
            default => 'text-sand-900 dark:text-sand-100',
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.budget-outcome-stat');
    }
}
