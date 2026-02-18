@props(['party', 'position'])

@php
    $classes = match($position) {
        'favor' => 'bg-republic-100 text-republic-800 dark:bg-republic-950 dark:text-republic-300',
        'contra' => 'bg-parliament-100 text-parliament-800 dark:bg-parliament-950 dark:text-parliament-300',
        'abstencao' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
        default => 'bg-sand-100 text-sand-800 dark:bg-sand-800 dark:text-sand-300',
    };
@endphp

<span {{ $attributes->class(["inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium $classes"]) }}>
    {{ $party }}
</span>
