<?php

namespace App\View\Components;

use App\Models\Budget;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A budget against the one of the year before: the totals with their difference, the amendments
 * approved by the end of each voting day, and each party's amendments in both years.
 */
class BudgetComparison extends Component
{
    public const ARROWS = ['up' => '▲', 'down' => '▼', 'same' => '='];

    /**
     * @param  array{previous: Budget, totals: list<array{key: string, current: int|float, previous: int|float}>, parties: list<array{acronym: string, current: ?array{proposed: int, approved: int, approval_rate: float}, previous: ?array{proposed: int, approved: int, approval_rate: float}}>, pace: list<array{current: ?array{decided: int, approved: int}, previous: ?array{decided: int, approved: int}}>}|null  $comparison
     */
    public function __construct(public Budget $budget, public ?array $comparison) {}

    public function shouldRender(): bool
    {
        return $this->comparison !== null;
    }

    public function previousYear(): int
    {
        return $this->comparison['previous']->year;
    }

    /**
     * Said when the budget of the year before was voted by another Parliament.
     */
    public function legislatureNote(): ?string
    {
        $previous = $this->comparison['previous'];

        if ($previous->legislature_id === $this->budget->legislature_id) {
            return null;
        }

        return __('ui.budget.comparison.other_legislature', ['year' => $previous->year, 'legislature' => $previous->legislature?->sigla]);
    }

    /**
     * @return list<array{label: string, current: string, previous: string, delta: string, direction: string, arrow: string}>
     */
    public function totalRows(): array
    {
        return array_map(function (array $row): array {
            $isRate = $row['key'] === 'approval_rate';

            return [
                'label' => __('ui.budget.comparison.total_'.$row['key']),
                'current' => $isRate ? $this->rate($row['current']) : $this->count($row['current']),
                'previous' => $isRate ? $this->rate($row['previous']) : $this->count($row['previous']),
                'delta' => $isRate ? $this->pointsDelta($row['current'], $row['previous']) : $this->countDelta($row['current'], $row['previous']),
                'direction' => $this->direction($row['current'], $row['previous']),
                'arrow' => self::ARROWS[$this->direction($row['current'], $row['previous'])],
            ];
        }, $this->comparison['totals']);
    }

    public function hasPace(): bool
    {
        $pace = $this->comparison['pace'];

        return $pace !== [] && $pace[0]['current'] !== null && $pace[0]['previous'] !== null;
    }

    /**
     * @return list<array{day: int, current: ?int, previous: ?int, current_share: float, previous_share: float}>
     */
    public function paceRows(): array
    {
        $pace = $this->comparison['pace'];
        $most = max(1, ...array_map(fn (array $day): int => max($day['current']['approved'] ?? 0, $day['previous']['approved'] ?? 0), $pace));

        return array_map(fn (array $day, int $index): array => [
            'day' => $index + 1,
            'current' => $day['current']['approved'] ?? null,
            'previous' => $day['previous']['approved'] ?? null,
            'current_share' => (float) ($day['current']['approved'] ?? 0) / $most,
            'previous_share' => (float) ($day['previous']['approved'] ?? 0) / $most,
        ], $pace, array_keys($pace));
    }

    /**
     * Where this year stands by its last voting day, against the same day the year before.
     */
    public function paceSummary(): ?string
    {
        $days = array_values(array_filter($this->comparison['pace'], fn (array $day): bool => $day['current'] !== null));
        $last = end($days);

        if ($last === false || $last['previous'] === null) {
            return null;
        }

        return trans_choice('ui.budget.comparison.pace_summary', count($days), [
            'days' => count($days),
            'current' => $this->count($last['current']['approved']),
            'previous' => $this->count($last['previous']['approved']),
            'year' => $this->previousYear(),
        ]);
    }

    /**
     * @return list<array{acronym: string, proposed: string, proposed_before: string, rate: string, rate_before: string, rate_direction: string}>
     */
    public function partyRows(): array
    {
        return array_map(fn (array $party): array => [
            'acronym' => $party['acronym'],
            'proposed' => $party['current'] ? $this->count($party['current']['proposed']) : '—',
            'proposed_before' => $party['previous'] ? $this->count($party['previous']['proposed']) : '—',
            'rate' => $party['current'] ? $this->rate($party['current']['approval_rate']) : '—',
            'rate_before' => $party['previous'] ? $this->rate($party['previous']['approval_rate']) : '—',
            'rate_direction' => $party['current'] && $party['previous']
                ? $this->direction($party['current']['approval_rate'], $party['previous']['approval_rate'])
                : 'same',
        ], $this->comparison['parties']);
    }

    public function arrow(string $direction): string
    {
        return self::ARROWS[$direction];
    }

    private function count(int|float $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    private function rate(int|float $value): string
    {
        return number_format($value, 1, ',', '.').'%';
    }

    private function countDelta(int|float $current, int|float $previous): string
    {
        $difference = $current - $previous;
        $sign = $difference > 0 ? '+' : ($difference < 0 ? '−' : '');
        $delta = $sign.$this->count(abs($difference));

        if ($previous > 0 && $difference !== 0) {
            $delta .= ' ('.$sign.number_format(abs($difference) / $previous * 100, 0, ',', '.').'%)';
        }

        return $delta;
    }

    private function pointsDelta(float $current, float $previous): string
    {
        $difference = round($current - $previous, 1);
        $sign = $difference > 0 ? '+' : ($difference < 0 ? '−' : '');

        return __('ui.budget.comparison.points', ['value' => $sign.number_format(abs($difference), 1, ',', '.')]);
    }

    private function direction(int|float $current, int|float $previous): string
    {
        return match (true) {
            $current > $previous => 'up',
            $current < $previous => 'down',
            default => 'same',
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.budget-comparison');
    }
}
