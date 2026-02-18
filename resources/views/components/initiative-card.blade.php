@props(['initiative'])

<a href="{{ route('initiatives.show', $initiative) }}"
   {{ $attributes->class(['group block rounded-2xl border border-sand-200 bg-white p-5 transition-all hover:border-sand-300 hover:shadow-md dark:border-sand-800 dark:bg-sand-900 dark:hover:border-sand-700']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-medium text-sand-400 dark:text-sand-500">{{ $initiative->id }}</p>
            <h3 class="mt-1 line-clamp-2 text-sm font-semibold leading-snug text-sand-900 transition-colors group-hover:text-republic-700 dark:text-sand-100 dark:group-hover:text-republic-400">
                {{ $initiative->title }}
            </h3>
        </div>
        <x-status-badge :status="$initiative->status" size="xs" class="shrink-0" />
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-sand-500 dark:text-sand-400">
        @if ($initiative->author_category === 'government')
            <x-government-badge />
        @else
            <span>{{ $initiative->author_label ?? __('ui.author_type.Other') }}</span>
        @endif

        @if ($initiative->entry_date)
            <span>&middot;</span>
            <span>{{ $initiative->entry_date->format('d/m/Y') }}</span>
        @endif
    </div>
</a>
