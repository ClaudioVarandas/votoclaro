<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A thin bar split into shares, in steps of 5% that always add up to the full width: a party's
 * votes (for, against, abstention) or the initiatives by outcome. The shares come in the order
 * of the colors below.
 */
class ShareBar extends Component
{
    private const STEPS = 20;

    /** Widths written out so the stylesheet keeps them. */
    private const WIDTHS = [
        1 => 'w-1/20',
        2 => 'w-2/20',
        3 => 'w-3/20',
        4 => 'w-4/20',
        5 => 'w-5/20',
        6 => 'w-6/20',
        7 => 'w-7/20',
        8 => 'w-8/20',
        9 => 'w-9/20',
        10 => 'w-10/20',
        11 => 'w-11/20',
        12 => 'w-12/20',
        13 => 'w-13/20',
        14 => 'w-14/20',
        15 => 'w-15/20',
        16 => 'w-16/20',
        17 => 'w-17/20',
        18 => 'w-18/20',
        19 => 'w-19/20',
        20 => 'w-full',
    ];

    private const COLORS = [
        'favor' => 'bg-republic-500 dark:bg-republic-400',
        'contra' => 'bg-parliament-500 dark:bg-parliament-400',
        'abstencao' => 'bg-amber-400 dark:bg-amber-400',
        'approved' => 'bg-republic-500 dark:bg-republic-400',
        'rejected' => 'bg-parliament-500 dark:bg-parliament-400',
        'in_progress' => 'bg-amber-400 dark:bg-amber-400',
        'lapsed' => 'bg-sand-400 dark:bg-sand-500',
        'withdrawn' => 'bg-sand-300 dark:bg-sand-600',
    ];

    /**
     * @param  array<string, int>  $counts  by a key of the colors
     * @param  bool  $thick  for a bar that stands on its own rather than in a table row
     */
    public function __construct(public array $counts, public bool $thick = false) {}

    public function heightClass(): string
    {
        return $this->thick ? 'h-2.5' : 'h-1.5';
    }

    /**
     * The segments to draw, each with its width and color; the steps are shared out by the
     * largest remainders, so they fill the bar exactly.
     *
     * @return list<array{position: string, classes: string}>
     */
    public function segments(): array
    {
        $counts = [];

        foreach (array_keys(self::COLORS) as $key) {
            if (isset($this->counts[$key])) {
                $counts[$key] = max(0, (int) $this->counts[$key]);
            }
        }

        $total = array_sum($counts);

        if ($total === 0) {
            return [];
        }

        $steps = [];
        $remainders = [];

        foreach ($counts as $position => $count) {
            $exact = $count / $total * self::STEPS;
            $steps[$position] = (int) floor($exact);
            $remainders[$position] = $exact - $steps[$position];
        }

        arsort($remainders);

        foreach (array_slice(array_keys($remainders), 0, self::STEPS - array_sum($steps)) as $position) {
            $steps[$position]++;
        }

        $segments = [];

        foreach ($steps as $position => $width) {
            if ($width > 0) {
                $segments[] = ['position' => $position, 'classes' => self::COLORS[$position].' '.self::WIDTHS[$width]];
            }
        }

        return $segments;
    }

    public function render(): View|Closure|string
    {
        return view('components.share-bar');
    }
}
