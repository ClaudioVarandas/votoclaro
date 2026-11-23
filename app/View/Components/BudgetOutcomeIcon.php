<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The tinted icon of a budget total: approved, rejected, still to vote, or all the proposals.
 */
class BudgetOutcomeIcon extends Component
{
    private const PATHS = [
        'approved' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'rejected' => 'm9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'pending' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'proposals' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
    ];

    public function __construct(public string $outcome) {}

    public function path(): string
    {
        return self::PATHS[$this->outcome] ?? self::PATHS['proposals'];
    }

    public function colorClasses(): string
    {
        return match ($this->outcome) {
            'approved' => 'bg-republic-100 text-republic-700 dark:bg-republic-950 dark:text-republic-400',
            'rejected' => 'bg-parliament-100 text-parliament-700 dark:bg-parliament-950 dark:text-parliament-400',
            'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
            default => 'bg-sand-100 text-sand-600 dark:bg-sand-800 dark:text-sand-400',
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.budget-outcome-icon');
    }
}
