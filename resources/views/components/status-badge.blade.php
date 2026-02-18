@props(['status', 'size' => 'sm'])

@php
    $classes = match($status) {
        'approved' => 'bg-republic-100 text-republic-800 dark:bg-republic-950 dark:text-republic-300',
        'rejected' => 'bg-parliament-100 text-parliament-800 dark:bg-parliament-950 dark:text-parliament-300',
        'in_progress' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
        default => 'bg-sand-100 text-sand-800 dark:bg-sand-800 dark:text-sand-300',
    };

    $sizeClasses = match($size) {
        'xs' => 'px-1.5 py-0.5 text-xs',
        'sm' => 'px-2 py-1 text-xs',
        'md' => 'px-2.5 py-1 text-sm',
        default => 'px-2 py-1 text-xs',
    };

    $icon = match($status) {
        'approved' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />',
        'rejected' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />',
        'in_progress' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
        default => '',
    };
@endphp

<span {{ $attributes->class(["inline-flex items-center gap-1 rounded-full font-medium $classes $sizeClasses"]) }}>
    @if ($icon)
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5">
            {!! $icon !!}
        </svg>
    @endif
    {{ __('ui.status.' . $status) }}
</span>
