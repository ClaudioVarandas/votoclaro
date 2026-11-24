@extends('layouts.app')

<x-seo-meta
    :title="__('ui.budget.name', ['year' => $budget->year]) . ' — VotoClaro'"
    :description="__('ui.budget.description')"
/>

@section('content')
    <div class="space-y-8">
        <nav aria-label="Breadcrumbs">
            <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm text-sand-500 dark:text-sand-400">
                <li><a href="{{ $legislatureContext->route('budgets.index') }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">{{ __('ui.breadcrumbs.budgets') }}</a></li>
                <li aria-hidden="true">/</li>
                <li>{{ __('ui.budget.short_name', ['year' => $budget->year]) }}</li>
            </ol>
        </nav>

        <div class="space-y-3">
            <h1 class="font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">{{ __('ui.budget.name', ['year' => $budget->year]) }}</h1>
            <p class="max-w-3xl text-sand-600 dark:text-sand-400">{{ __('ui.budget.what_is') }}</p>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                @if ($budget->initiative)
                    <a href="{{ route('initiatives.show', $budget->initiative) }}" class="font-medium text-republic-600 underline hover:text-republic-700 dark:text-republic-400 dark:hover:text-republic-300">{{ __('ui.budget.see_bill') }}</a>
                @endif
                <a href="{{ $legislatureContext->route('budgets.proposals.index', ['year' => $budget->year]) }}" class="font-medium text-republic-600 underline hover:text-republic-700 dark:text-republic-400 dark:hover:text-republic-300">{{ __('ui.budget.see_proposals') }}</a>
            </div>
        </div>

        {{-- Overview --}}
        <section class="space-y-3">
            <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach (['total' => 'proposals', 'approved' => 'approved', 'rejected' => 'rejected', 'pending' => 'pending'] as $key => $outcome)
                    <x-budget-outcome-stat :outcome="$outcome" :label="__('ui.budget.overview.' . $outcome)" :value="$overview[$key]" />
                @endforeach
            </dl>
            @if ($overview['pending'] > 0)
                <p class="text-sm text-sand-500 dark:text-sand-400">{{ __('ui.budget.voting_progress', ['voted' => number_format($overview['voted'], 0, ',', '.'), 'total' => number_format($overview['total'], 0, ',', '.')]) }}</p>
            @endif
            @if ($overview['against_government'])
                <a href="{{ $legislatureContext->route('budgets.proposals.index', ['year' => $budget->year, 'contra_governo' => 1], $budget->legislature) }}" class="group flex flex-col gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 p-5 transition-colors hover:border-indigo-300 sm:flex-row sm:items-center dark:border-indigo-900 dark:bg-indigo-950/60 dark:hover:border-indigo-700">
                    <span class="font-serif text-3xl font-bold tabular-nums text-indigo-800 dark:text-indigo-300">{{ number_format($overview['against_government'], 0, ',', '.') }}</span>
                    <span class="flex-1">
                        <span class="block font-semibold text-indigo-900 dark:text-indigo-200">{{ __('ui.budget.against_government.label') }}</span>
                        <span class="block text-sm text-indigo-800/80 dark:text-indigo-300/80">{{ __('ui.budget.against_government.explain', ['parties' => implode(', ', $government)]) }}</span>
                    </span>
                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                        {{ __('ui.budget.against_government.see') }}
                        <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">→</span>
                    </span>
                </a>
            @endif
        </section>

        {{-- Parties --}}
        <x-section-panel id="parties-title" :title="__('ui.budget.parties_title')" :intro="__('ui.budget.parties_intro')" icon="building-library">
            <div class="-mx-5 overflow-x-auto sm:-mx-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-y border-sand-200 bg-sand-50 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 dark:border-sand-800 dark:bg-sand-950 dark:text-sand-400">
                            <th scope="col" class="py-3 pr-2 pl-5 sm:px-6">{{ __('ui.budget.party') }}</th>
                            <th scope="col" class="px-2 py-3 text-right sm:px-4">{{ __('ui.budget.proposed') }}</th>
                            <th scope="col" class="px-2 py-3 sm:w-1/4 sm:px-4">{{ __('ui.budget.approved') }}</th>
                            @if ($articles['voted'] > 0)
                                <th scope="col" class="hidden px-4 py-3 md:table-cell md:w-1/5">{{ __('ui.budget.articles.party_column') }}</th>
                            @endif
                            <th scope="col" class="py-3 pr-5 pl-2 sm:w-1/3 sm:px-6"><span class="sm:hidden">{{ __('ui.budget.against_others_short') }}</span><span class="hidden sm:inline">{{ __('ui.budget.against_others') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand-100 dark:divide-sand-800">
                        @foreach ($parties as $party)
                            <tr class="transition-colors hover:bg-sand-50 dark:hover:bg-sand-950">
                                <th scope="row" class="py-3 pr-2 pl-5 text-left sm:px-6">
                                    @if ($party['proposed'] > 0)
                                        <a href="{{ $legislatureContext->route('budgets.proposals.index', ['year' => $budget->year, 'partido' => strtolower($party['acronym'])]) }}"
                                           class="inline-flex justify-center whitespace-nowrap rounded-lg sm:min-w-14 bg-sand-100 px-2.5 py-1 text-sm font-bold text-sand-900 transition-colors hover:bg-republic-100 hover:text-republic-800 dark:bg-sand-800 dark:text-sand-100 dark:hover:bg-republic-950 dark:hover:text-republic-300">{{ $party['acronym'] }}</a>
                                    @else
                                        <span class="inline-flex justify-center whitespace-nowrap rounded-lg sm:min-w-14 bg-sand-100 px-2.5 py-1 text-sm font-bold text-sand-900 dark:bg-sand-800 dark:text-sand-100">{{ $party['acronym'] }}</span>
                                    @endif
                                </th>
                                <td class="px-2 py-3 text-right font-serif text-base font-bold tabular-nums sm:px-4 sm:text-lg text-sand-900 dark:text-sand-100">{{ number_format($party['proposed'], 0, ',', '.') }}</td>
                                <td class="px-2 py-3 sm:px-4">
                                    @if ($party['proposed'] > 0)
                                        <div class="flex items-baseline justify-between gap-2">
                                            <span class="font-semibold tabular-nums text-republic-700 dark:text-republic-400">{{ number_format($party['approval_rate'], 1, ',', '.') }}%</span>
                                            <span class="hidden text-xs tabular-nums text-sand-500 sm:inline dark:text-sand-400">{{ __('ui.budget.of_count', ['part' => number_format($party['approved'], 0, ',', '.'), 'total' => number_format($party['proposed'], 0, ',', '.')]) }}</span>
                                        </div>
                                        <x-progress-bar :share="$party['approval_rate'] / 100" class="mt-1.5" />
                                        @if ($party['approved_against_government'] > 0)
                                            <a href="{{ $legislatureContext->route('budgets.proposals.index', ['year' => $budget->year, 'partido' => strtolower($party['acronym']), 'contra_governo' => 1], $budget->legislature) }}" class="mt-1 inline-block text-xs font-medium text-indigo-700 hover:underline dark:text-indigo-300">{{ trans_choice('ui.budget.against_government.party_count', $party['approved_against_government'], ['count' => number_format($party['approved_against_government'], 0, ',', '.')]) }}</a>
                                        @endif
                                    @else
                                        <span class="text-sand-400 dark:text-sand-600">—</span>
                                    @endif
                                </td>
                                @if ($articles['voted'] > 0)
                                    <td class="hidden px-4 py-3 md:table-cell">
                                        @if ($party['articles_voted'] > 0)
                                            <div class="flex items-baseline justify-between gap-2">
                                                <span class="font-semibold tabular-nums text-indigo-700 dark:text-indigo-400">{{ __('ui.budget.articles.favor_rate', ['percent' => number_format($party['articles_favor_rate'], 0, ',', '.')]) }}</span>
                                            </div>
                                            <x-share-bar :counts="$party['articles']" class="mt-1.5" title="{{ __('ui.budget.articles.share_title', ['favor' => $party['articles']['favor'], 'contra' => $party['articles']['contra'], 'abstencao' => $party['articles']['abstencao']]) }}" />
                                        @else
                                            <span class="text-sand-400 dark:text-sand-600">—</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="py-3 pr-5 pl-2 sm:px-6">
                                    @if ($party['others_voted'] > 0)
                                        <div class="flex items-baseline justify-between gap-2">
                                            <span class="font-semibold tabular-nums text-parliament-700 dark:text-parliament-400">{{ number_format($party['against_rate'], 1, ',', '.') }}%</span>
                                            <span class="hidden text-xs tabular-nums text-sand-500 sm:inline dark:text-sand-400">{{ __('ui.budget.of_count', ['part' => number_format($party['against_others'], 0, ',', '.'), 'total' => number_format($party['others_voted'], 0, ',', '.')]) }}</span>
                                        </div>
                                        <x-progress-bar :share="$party['against_rate'] / 100" color="parliament" class="mt-1.5" />
                                    @else
                                        <span class="text-sand-400 dark:text-sand-600">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-section-panel>

        {{-- The Government's bill --}}
        @if ($articles['total'] > 0)
            <x-section-panel id="articles-title" :title="__('ui.budget.articles.section_title')" :intro="__('ui.budget.articles.section_intro')" icon="building-library" accent="indigo">
                <div class="space-y-6">
                    <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        @foreach (['total' => 'proposals', 'approved' => 'approved', 'pending' => 'pending'] as $key => $outcome)
                            <x-budget-outcome-stat compact :outcome="$outcome" :label="__('ui.budget.articles.stat_' . $key)" :value="$articles[$key]" />
                        @endforeach
                        <x-budget-outcome-stat compact outcome="proposals" :label="__('ui.budget.articles.stat_voted')" :value="$articles['voted']" />
                    </dl>

                    @if ($articles['most_amended']->isNotEmpty())
                        <div>
                            <h3 class="text-sm font-semibold text-sand-900 dark:text-sand-100">{{ __('ui.budget.articles.most_amended') }}</h3>
                            <ol class="mt-2 grid grid-cols-1 gap-x-8 gap-y-1 md:grid-cols-2">
                                @foreach ($articles['most_amended'] as $article)
                                    <li>
                                        <a href="{{ $legislatureContext->route('budgets.articles.show', ['year' => $budget->year, 'article' => $article->slug]) }}" class="group flex items-center gap-3 rounded-xl px-2 py-2 transition-colors hover:bg-sand-50 dark:hover:bg-sand-950">
                                            <span class="w-24 shrink-0 text-xs font-semibold text-indigo-700 dark:text-indigo-400">{{ $article->number }}</span>
                                            <span class="min-w-0 flex-1">
                                                <span class="flex items-baseline justify-between gap-3">
                                                    <span class="truncate text-sm font-medium text-sand-800 group-hover:text-indigo-700 dark:text-sand-200 dark:group-hover:text-indigo-400">{{ $article->title ?? $article->number }}</span>
                                                    <span class="shrink-0 text-sm font-semibold tabular-nums text-sand-900 dark:text-sand-100">{{ $article->proposals_count }}</span>
                                                </span>
                                                <x-progress-bar :share="$article->proposals_count / $articles['most_amended']->max('proposals_count')" color="indigo" class="mt-1.5" />
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif

                    <a href="{{ $legislatureContext->route('budgets.articles.index', ['year' => $budget->year]) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-700 hover:underline dark:text-indigo-400">{{ __('ui.budget.articles.see_all') }} <span aria-hidden="true">→</span></a>
                </div>
            </x-section-panel>
        @endif

        {{-- Topics --}}
        @if ($topics['proposals']->isNotEmpty())
            <x-section-panel id="topics-title" :title="__('ui.budget.topics.title')" :intro="__('ui.budget.topics.intro')" icon="chat" x-data="{ tab: 'parties' }">
                <div class="space-y-4">
                <div role="tablist" aria-label="{{ __('ui.budget.topics.title') }}" class="inline-flex flex-wrap gap-1 rounded-xl bg-sand-100 p-1 dark:bg-sand-950">
                    @foreach (['parties', 'proposals', 'divided'] as $rankBy)
                        <button type="button" role="tab" id="topics-tab-{{ $rankBy }}" aria-controls="topics-panel-{{ $rankBy }}"
                                @click="tab = '{{ $rankBy }}'"
                                :aria-selected="tab === '{{ $rankBy }}'"
                                :class="tab === '{{ $rankBy }}' ? 'bg-white text-republic-700 shadow-sm dark:bg-sand-800 dark:text-republic-400' : 'text-sand-600 hover:text-republic-700 dark:text-sand-400 dark:hover:text-republic-400'"
                                class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors">
                            {{ __('ui.budget.topics.tab_' . $rankBy) }}
                        </button>
                    @endforeach
                </div>
                @foreach (['parties', 'proposals', 'divided'] as $rankBy)
                    <div role="tabpanel" id="topics-panel-{{ $rankBy }}" aria-labelledby="topics-tab-{{ $rankBy }}" x-show="tab === '{{ $rankBy }}'" @if (! $loop->first) x-cloak @endif class="space-y-3">
                        <p class="text-sm text-sand-600 dark:text-sand-400">{{ __('ui.budget.topics.hint_' . $rankBy) }}</p>
                        @if ($topics[$rankBy]->isEmpty())
                            <p class="rounded-xl border border-sand-200 bg-white p-4 text-sm text-sand-500 dark:border-sand-800 dark:bg-sand-900 dark:text-sand-400">{{ __('ui.budget.topics.no_divided') }}</p>
                        @else
                            <x-budget-topic-list :budget="$budget" :topics="$topics[$rankBy]" :rank-by="$rankBy" />
                        @endif
                        @if ($rankBy === 'divided')
                            <p class="text-xs text-sand-500 dark:text-sand-400">{{ __('ui.budget.topics.division_help') }}</p>
                        @endif
                    </div>
                @endforeach
                </div>
            </x-section-panel>
        @endif

        {{-- Themes --}}
        @if ($themes->isNotEmpty())
            <x-section-panel id="themes-title" :title="__('ui.budget.themes_title')" :intro="__('ui.budget.themes_intro')" icon="tag" accent="blue">
                <ul class="grid grid-cols-1 gap-x-8 gap-y-1 md:grid-cols-2">
                    @foreach ($themes as $theme)
                        <li>
                            <a href="{{ $legislatureContext->route('budgets.proposals.index', ['year' => $budget->year, 'tema' => $theme->slug]) }}"
                               class="group flex items-center gap-3 rounded-xl px-2 py-2 transition-colors hover:bg-sand-50 dark:hover:bg-sand-950">
                                <span class="inline-flex shrink-0 rounded-lg bg-sky-50 p-1.5 text-sky-700 dark:bg-sky-950 dark:text-sky-400">
                                    <x-budget-theme-icon :theme="$theme->name" class="size-4" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-baseline justify-between gap-3">
                                        <span class="truncate text-sm font-medium text-sand-800 group-hover:text-sky-700 dark:text-sand-200 dark:group-hover:text-sky-400">{{ $theme->name }}</span>
                                        <span class="shrink-0 text-sm font-semibold tabular-nums text-sand-900 dark:text-sand-100">{{ number_format($theme->proposals_count, 0, ',', '.') }}</span>
                                    </span>
                                    <x-progress-bar :share="$theme->proposals_count / $themes->max('proposals_count')" color="blue" class="mt-1.5" />
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-section-panel>
        @endif

        @if ($budget->last_imported_at)
            <p class="text-xs text-sand-500 dark:text-sand-400">{{ __('ui.budget.last_imported', ['date' => $budget->last_imported_at->format('d/m/Y')]) }}</p>
        @endif
    </div>
@endsection
