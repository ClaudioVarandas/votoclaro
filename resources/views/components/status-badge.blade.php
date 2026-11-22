@use('App\Enums\InitiativeStatus')
@props(['status', 'size' => 'sm'])

@php
    $sizeClasses = match($size) {
        'xs' => 'px-1.5 py-0.5 text-xs',
        'sm' => 'px-2 py-1 text-xs',
        'md' => 'px-2.5 py-1 text-sm',
        default => 'px-2 py-1 text-xs',
    };

    $icon = match($status) {
        InitiativeStatus::Approved => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />',
        InitiativeStatus::Rejected => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />',
        InitiativeStatus::InProgress => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
        InitiativeStatus::Lapsed => '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />',
        InitiativeStatus::Withdrawn => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />',
    };
@endphp

<span {{ $attributes->class(["inline-flex items-center gap-1 rounded-full font-medium {$status->color()} $sizeClasses"]) }}>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5">
        {!! $icon !!}
    </svg>
    {{ $status->label() }}
</span>
