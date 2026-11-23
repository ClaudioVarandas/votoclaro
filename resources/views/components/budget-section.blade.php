@props(['id', 'title', 'intro' => null, 'icon', 'accent' => 'green'])

<section {{ $attributes->class(['rounded-2xl border border-sand-200 bg-white p-5 sm:p-6 dark:border-sand-800 dark:bg-sand-900']) }} aria-labelledby="{{ $id }}">
    <div class="flex items-start gap-3">
        <span @class([
            'inline-flex shrink-0 rounded-xl p-2',
            'bg-republic-100 text-republic-700 dark:bg-republic-950 dark:text-republic-400' => $accent === 'green',
            'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-400' => $accent === 'blue',
            'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-400' => $accent === 'indigo',
        ])>
            <x-budget-theme-icon :icon="$icon" class="size-5" />
        </span>
        <div class="min-w-0">
            <h2 id="{{ $id }}" class="font-serif text-2xl font-bold tracking-tight text-sand-900 dark:text-sand-100">{{ $title }}</h2>
            @if ($intro)
                <p class="mt-1 text-sm text-sand-500 dark:text-sand-400">{{ $intro }}</p>
            @endif
        </div>
    </div>
    <div class="mt-5">
        {{ $slot }}
    </div>
</section>
