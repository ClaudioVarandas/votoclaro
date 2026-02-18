@props(['stats'])

<a href="{{ route('parties.show', strtolower($stats['acronym'])) }}"
   {{ $attributes->class(['group block rounded-2xl border border-sand-200 bg-white p-5 transition-all hover:border-republic-300 hover:shadow-md dark:border-sand-800 dark:bg-sand-900 dark:hover:border-republic-700']) }}>
    <div class="flex items-center justify-between">
        <h3 class="font-serif text-lg font-bold text-sand-900 group-hover:text-republic-700 dark:text-sand-100 dark:group-hover:text-republic-400">
            {{ $stats['acronym'] }}
        </h3>
        <span class="text-sm text-sand-500 dark:text-sand-400">
            {{ $stats['total_votes'] }} {{ __('ui.parties.total_votes') }}
        </span>
    </div>

    @if ($stats['total_votes'] > 0)
        <div class="mt-4">
            <x-vote-summary-bar :counts="['favor' => $stats['favor'], 'contra' => $stats['contra'], 'abstencao' => $stats['abstencao']]" />
        </div>

        <div class="mt-3 flex items-center justify-between text-xs text-sand-500 dark:text-sand-400">
            <span>{{ __('ui.parties.government_alignment') }}: {{ $stats['government_alignment'] }}%</span>
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sand-400 transition-transform group-hover:translate-x-0.5 dark:text-sand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </div>
    @else
        <p class="mt-4 text-sm text-sand-400 dark:text-sand-600">{{ __('ui.parties.no_data') }}</p>
    @endif
</a>
