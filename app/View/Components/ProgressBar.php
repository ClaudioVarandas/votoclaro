<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A thin bar filled to a share, in steps of 5%, for figures read at a glance.
 */
class ProgressBar extends Component
{
    /** Widths written out so the stylesheet keeps them. */
    private const WIDTHS = [
        'w-0',
        'w-1/20',
        'w-2/20',
        'w-3/20',
        'w-4/20',
        'w-5/20',
        'w-6/20',
        'w-7/20',
        'w-8/20',
        'w-9/20',
        'w-10/20',
        'w-11/20',
        'w-12/20',
        'w-13/20',
        'w-14/20',
        'w-15/20',
        'w-16/20',
        'w-17/20',
        'w-18/20',
        'w-19/20',
        'w-full',
    ];

    private const COLORS = [
        'republic' => 'bg-republic-500 dark:bg-republic-400',
        'parliament' => 'bg-parliament-500 dark:bg-parliament-400',
        'amber' => 'bg-amber-500 dark:bg-amber-400',
        'blue' => 'bg-sky-600 dark:bg-sky-400',
        'indigo' => 'bg-indigo-500 dark:bg-indigo-400',
        'sand' => 'bg-sand-400 dark:bg-sand-500',
    ];

    /**
     * @param  float  $share  from 0 to 1; anything outside is clamped
     */
    public function __construct(public float $share, public string $color = 'republic') {}

    public function widthClass(): string
    {
        return self::WIDTHS[(int) round(max(0, min(1, $this->share)) * 20)];
    }

    public function colorClass(): string
    {
        return self::COLORS[$this->color] ?? self::COLORS['republic'];
    }

    public function render(): View|Closure|string
    {
        return view('components.progress-bar');
    }
}
