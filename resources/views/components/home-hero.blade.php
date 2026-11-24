<section {{ $attributes->class(['relative overflow-hidden rounded-3xl border border-republic-100 bg-gradient-to-br from-republic-50 via-white to-sand-50 shadow-sm dark:border-sand-800 dark:from-republic-950/60 dark:via-sand-900 dark:to-sand-900']) }} aria-labelledby="hero-title">
    <div class="pointer-events-none absolute -top-24 -right-24 size-96 rounded-full bg-republic-200/40 blur-3xl dark:bg-republic-800/20" aria-hidden="true"></div>

    <div class="relative grid gap-10 p-6 sm:p-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
        <div class="space-y-6">
            @if ($hasBudget())
                @php($overview = $highlight['overview'])
                <p class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wider text-republic-800 shadow-sm ring-1 ring-republic-100 dark:bg-sand-800 dark:text-republic-300 dark:ring-sand-700">
                    <span class="relative flex size-2" aria-hidden="true">
                        @if ($isVoting())
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                        @endif
                        <span @class([
                            'relative inline-flex size-2 rounded-full',
                            'bg-amber-500' => $isVoting(),
                            'bg-sky-500' => $isSubmitted(),
                            'bg-republic-500' => $isClosed(),
                        ])></span>
                    </span>
                    {{ $badge() }}
                </p>

                <div class="space-y-3">
                    <h1 id="hero-title" class="max-w-2xl font-serif text-3xl font-bold leading-tight tracking-tight text-sand-900 sm:text-5xl dark:text-sand-100">{{ $title() }}</h1>
                    <p class="max-w-xl text-base text-sand-600 sm:text-lg dark:text-sand-400">{{ $lead() }}</p>
                </div>

                @if ($isVoting())
                    <div class="max-w-xl space-y-2">
                        <x-progress-bar :share="$votedShare()" color="amber" thick />
                        <p class="text-sm text-sand-600 dark:text-sand-400">{{ __('ui.budget.voting_progress', ['voted' => $figure($overview['voted']), 'total' => $figure($overview['total'])]) }}</p>
                    </div>
                @elseif ($isClosed() && $overview['approved'] + $overview['rejected'] > 0)
                    <div class="max-w-xl">
                        <x-share-bar :counts="['approved' => $overview['approved'], 'rejected' => $overview['rejected']]" thick />
                    </div>
                @endif

                <dl class="flex flex-wrap gap-x-8 gap-y-3">
                    @foreach ($figures() as $key => $label)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ $label }}</dt>
                            <dd @class(['font-serif text-3xl font-bold tabular-nums', $figureClasses($key)])>{{ $figure($overview[$key]) }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($againstGovernment())
                    <a href="{{ $againstGovernmentUrl() }}" class="group inline-flex items-center gap-2 rounded-xl bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-800 ring-1 ring-indigo-200 transition-colors hover:bg-indigo-100 dark:bg-indigo-950 dark:text-indigo-300 dark:ring-indigo-900 dark:hover:bg-indigo-900">
                        {{ $againstGovernment() }}
                        <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">→</span>
                    </a>
                @endif

                @if (collect($highlight['topics'])->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm text-sand-600 dark:text-sand-400">{{ __('ui.home.topics') }}</span>
                        @foreach ($highlight['topics'] as $topic)
                            <a href="{{ $topicUrl($topic) }}" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-sm font-medium text-sand-800 shadow-sm ring-1 ring-sand-200 transition-colors hover:text-republic-700 hover:ring-republic-300 dark:bg-sand-800 dark:text-sand-200 dark:ring-sand-700 dark:hover:text-republic-400">
                                <x-budget-theme-icon :theme="$topic->label" class="size-4" />
                                {{ $topic->label }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-wrap gap-3">
                    <a href="{{ $budgetUrl() }}" class="inline-flex items-center rounded-xl bg-republic-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-republic-700 dark:bg-republic-500 dark:hover:bg-republic-600">{{ __('ui.home.cta_budget') }}</a>
                    <a href="{{ $matrixUrl() }}" class="group inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-republic-700 shadow-sm ring-1 ring-sand-200 transition-colors hover:ring-republic-300 dark:bg-sand-800 dark:text-republic-400 dark:ring-sand-700">
                        {{ __('ui.home.cta_matrix') }}
                        <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">→</span>
                    </a>
                </div>
            @else
                <h1 id="hero-title" class="max-w-2xl font-serif text-3xl font-bold leading-tight tracking-tight text-sand-900 sm:text-5xl dark:text-sand-100">{{ __('ui.home.intro_title') }}</h1>
                <p class="max-w-xl text-base text-sand-600 sm:text-lg dark:text-sand-400">{{ __('ui.home.intro_lead') }}</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ $legislatureContext->route('initiatives.index') }}" class="inline-flex items-center rounded-xl bg-republic-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-republic-700 dark:bg-republic-500 dark:hover:bg-republic-600">{{ __('ui.home.cta_initiatives') }}</a>
                    <a href="{{ $legislatureContext->route('parties.index') }}" class="inline-flex items-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-republic-700 shadow-sm ring-1 ring-sand-200 transition-colors hover:ring-republic-300 dark:bg-sand-800 dark:text-republic-400 dark:ring-sand-700">{{ __('ui.home.cta_parties') }}</a>
                </div>
            @endif
        </div>

        @if ($showsSample())
            <figure class="hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-sand-200 lg:block dark:bg-sand-950 dark:ring-sand-800" aria-hidden="true">
                <div @class(['grid gap-1.5', $sampleColumnsClass()])>
                    @foreach ($highlight['sample']['parties'] as $party)
                        <span class="text-center text-[9px] font-semibold tracking-tight whitespace-nowrap text-sand-500 dark:text-sand-400">{{ $party }}</span>
                    @endforeach
                    @foreach ($highlight['sample']['rows'] as $row)
                        @foreach ($row as $position)
                            <span @class(['inline-flex size-8 items-center justify-center rounded-md text-sm font-bold', $cellClasses($position)])>{{ $symbol($position) }}</span>
                        @endforeach
                    @endforeach
                </div>
                <figcaption class="mt-3 text-center text-xs text-sand-500 dark:text-sand-400">{{ __('ui.home.sample_caption') }}</figcaption>
            </figure>
        @endif
    </div>
</section>
