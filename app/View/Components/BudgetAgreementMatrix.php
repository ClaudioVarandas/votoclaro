<?php

namespace App\View\Components;

use App\Models\Budget;
use App\Services\LegislatureContext;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Who votes with whom on a budget: a grid of the parties, each cell the share of amendments the
 * two voted alike, linking to the ones they voted apart. On a phone, one party's list instead.
 */
class BudgetAgreementMatrix extends Component
{
    /** Shades from the least to the most agreement, a step every 20 points. */
    private const SHADES = [
        'bg-sand-100 text-sand-700 dark:bg-sand-800 dark:text-sand-300',
        'bg-republic-100 text-republic-900 dark:bg-republic-950 dark:text-republic-200',
        'bg-republic-200 text-republic-900 dark:bg-republic-900 dark:text-republic-100',
        'bg-republic-400 text-white dark:bg-republic-700',
        'bg-republic-600 text-white dark:bg-republic-500',
    ];

    private const NO_RATE = 'bg-sand-50 text-sand-400 dark:bg-sand-950 dark:text-sand-600';

    /**
     * @param  array{parties: list<string>, pairs: array<string, array<string, array{voted: int, same: int, rate: ?float}>>}  $agreement
     */
    public function __construct(
        private LegislatureContext $legislatureContext,
        public Budget $budget,
        public array $agreement,
    ) {}

    public function shouldRender(): bool
    {
        return count($this->agreement['parties']) > 1;
    }

    /**
     * @return list<string>
     */
    public function parties(): array
    {
        return $this->agreement['parties'];
    }

    public function rate(string $first, string $second): ?float
    {
        return $this->agreement['pairs'][$first][$second]['rate'] ?? null;
    }

    public function percent(string $first, string $second): string
    {
        $rate = $this->rate($first, $second);

        return $rate === null ? '—' : number_format($rate, 0, ',', '.').'%';
    }

    public function shadeClass(string $first, string $second): string
    {
        $rate = $this->rate($first, $second);

        return $rate === null ? self::NO_RATE : self::SHADES[min(4, (int) floor($rate / 20))];
    }

    /**
     * @return list<string>
     */
    public function legendShades(): array
    {
        return self::SHADES;
    }

    public function apartCount(string $first, string $second): int
    {
        $pair = $this->agreement['pairs'][$first][$second] ?? ['voted' => 0, 'same' => 0];

        return $pair['voted'] - $pair['same'];
    }

    public function url(string $first, string $second): string
    {
        return $this->legislatureContext->route(
            'budgets.proposals.index',
            ['year' => $this->budget->year, 'divergem' => strtolower("{$first},{$second}")],
            $this->budget->legislature,
        );
    }

    public function describe(string $first, string $second): string
    {
        $pair = $this->agreement['pairs'][$first][$second] ?? ['voted' => 0, 'same' => 0];

        return trans_choice('ui.budget.agreement.cell', $pair['voted'], [
            'first' => $first,
            'second' => $second,
            'same' => number_format($pair['same'], 0, ',', '.'),
            'count' => number_format($pair['voted'], 0, ',', '.'),
        ]);
    }

    /**
     * The other parties, from the one that voted most like this one to the least; the ones
     * without a rate last.
     *
     * @return list<string>
     */
    public function closestTo(string $party): array
    {
        return collect($this->parties())
            ->reject(fn (string $other): bool => $other === $party)
            ->sortBy([
                fn (string $a, string $b): int => ($this->rate($party, $a) === null) <=> ($this->rate($party, $b) === null),
                fn (string $a, string $b): int => ($this->rate($party, $b) ?? 0) <=> ($this->rate($party, $a) ?? 0),
            ])
            ->values()
            ->all();
    }

    public function render(): View|Closure|string
    {
        return view('components.budget-agreement-matrix');
    }
}
