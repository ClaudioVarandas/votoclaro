@props(['title', 'value'])

<div {{ $attributes->class(['flex flex-col rounded-2xl border border-sand-200 bg-white p-6 dark:border-sand-800 dark:bg-sand-900']) }}>
    <div class="flex items-center gap-2">
        @if (isset($icon))
            {{ $icon }}
        @endif
        <p class="text-sm font-medium text-sand-500 dark:text-sand-400">{{ $title }}</p>
    </div>
    <p class="mt-2 font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">{{ $value }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-3 flex flex-1 flex-col justify-end">
            {{ $slot }}
        </div>
    @endif
</div>
