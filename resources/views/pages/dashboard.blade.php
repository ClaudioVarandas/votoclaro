@extends('layouts.app')

<x-seo-meta
    :title="__('ui.dashboard.title') . ' — VotoClaro'"
    :description="__('ui.dashboard.seo_description', [
        'total' => number_format($totalInitiatives, 0, ',', '.'),
        'approval_rate' => number_format($approvalRate, 1, ',', '.'),
        'gov_rate' => number_format($authorship['government']['approval_rate'], 1, ',', '.'),
    ])"
/>

@section('content')
    <div class="space-y-8">
        <x-home-hero :highlight="$budgetHighlight" />

        {{-- The legislature in figures --}}
        <x-section-panel id="numbers-title" :title="__('ui.dashboard.numbers_title')" :intro="__('ui.dashboard.description')" icon="chart-bar">
            <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-budget-outcome-stat outcome="proposals" :label="__('ui.dashboard.total_initiatives')" :value="$totalInitiatives" />
                <x-budget-outcome-stat outcome="approved" :label="__('ui.dashboard.outcome.approved')" :value="$approved" />
                <x-budget-outcome-stat outcome="rejected" :label="__('ui.dashboard.outcome.rejected')" :value="$rejected" />
                <x-budget-outcome-stat outcome="pending" :label="__('ui.dashboard.outcome.in_progress')" :value="$inProgress" />
            </dl>

            @if ($totalInitiatives > 0)
                @php
                    $outcomes = array_filter([
                        'approved' => $approved,
                        'rejected' => $rejected,
                        'in_progress' => $inProgress,
                        'lapsed' => $lapsed,
                        'withdrawn' => $withdrawn,
                    ]);
                @endphp
                <div class="mt-5 space-y-2">
                    <x-share-bar :counts="$outcomes" thick />
                    <ul class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-sand-600 dark:text-sand-400">
                        @foreach ($outcomes as $status => $count)
                            <li class="flex items-center gap-1.5">
                                <span @class([
                                    'inline-block size-2.5 rounded-full',
                                    'bg-republic-500' => $status === 'approved',
                                    'bg-parliament-500' => $status === 'rejected',
                                    'bg-amber-400' => $status === 'in_progress',
                                    'bg-sand-400' => $status === 'lapsed',
                                    'bg-sand-300 dark:bg-sand-600' => $status === 'withdrawn',
                                ])></span>
                                {{ __('ui.dashboard.outcome.' . $status) }}
                                <span class="font-semibold tabular-nums text-sand-800 dark:text-sand-200">{{ number_format($count, 0, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-section-panel>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            {{-- Government and parties --}}
            <x-section-panel id="authorship-title" :title="__('ui.dashboard.authorship_title')" :intro="__('ui.dashboard.authorship_intro')" icon="building-library" accent="indigo">
                <div class="grid grid-cols-2 gap-3">
                    @foreach (['government' => __('ui.government'), 'parliamentary_group' => __('ui.dashboard.parties_label')] as $category => $label)
                        @php $figures = $authorship[$category]; @endphp
                        <div class="rounded-xl bg-sand-50 p-4 dark:bg-sand-950">
                            <p class="text-sm font-semibold text-sand-700 dark:text-sand-300">{{ $label }}</p>
                            <p class="mt-1 font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">{{ number_format($figures['total'], 0, ',', '.') }}</p>
                            <p class="text-xs text-sand-500 dark:text-sand-400">{{ __('ui.dashboard.initiatives_proposed') }}</p>
                            <div class="mt-4 flex items-baseline justify-between gap-2">
                                <span class="font-semibold tabular-nums text-republic-700 dark:text-republic-400">{{ __('ui.dashboard.approved_rate', ['percent' => number_format($figures['approval_rate'], 1, ',', '.')]) }}</span>
                            </div>
                            <x-progress-bar :share="$figures['approval_rate'] / 100" class="mt-1.5" />
                            @if ($figures['avg_days'] !== null)
                                <p class="mt-3 text-xs text-sand-500 dark:text-sand-400">{{ trans_choice('ui.dashboard.avg_days', $figures['avg_days'], ['days' => $figures['avg_days']]) }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-section-panel>

            {{-- Consensus --}}
            <x-section-panel id="consensus-title" :title="__('ui.dashboard.consensus_title')" :intro="__('ui.dashboard.consensus_intro')" icon="scale" accent="blue">
                <div class="space-y-5">
                    <div>
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="text-sm font-semibold text-sand-700 dark:text-sand-300">{{ __('ui.dashboard.approval_rate') }}</p>
                            <p class="font-serif text-3xl font-bold tabular-nums text-sand-900 dark:text-sand-100">{{ number_format($approvalRate, 1, ',', '.') }}%</p>
                        </div>
                        <x-progress-bar :share="$approvalRate / 100" color="blue" class="mt-2" />
                        <p class="mt-1.5 text-xs text-sand-500 dark:text-sand-400">{{ __('ui.dashboard.approval_rate_help', ['approved' => number_format($approved, 0, ',', '.'), 'total' => number_format($totalInitiatives, 0, ',', '.')]) }}</p>
                    </div>
                    <div>
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="text-sm font-semibold text-sand-700 dark:text-sand-300">{{ __('ui.dashboard.unanimous_votes') }}</p>
                            <p class="font-serif text-3xl font-bold tabular-nums text-sand-900 dark:text-sand-100">{{ number_format($unanimousCount, 0, ',', '.') }}</p>
                        </div>
                        <x-progress-bar :share="$unanimousPct / 100" color="blue" class="mt-2" />
                        <p class="mt-1.5 text-xs text-sand-500 dark:text-sand-400">{{ __('ui.dashboard.unanimous_help', ['percent' => number_format($unanimousPct, 1, ',', '.')]) }}</p>
                    </div>
                </div>
            </x-section-panel>
        </div>

        {{-- Parties --}}
        <x-section-panel id="parties-title" :title="__('ui.dashboard.parties_title')" :intro="__('ui.dashboard.parties_intro')" icon="chart-bar">
            <x-slot:action>
                <a href="{{ $legislatureContext->route('parties.index') }}" class="hidden text-sm font-semibold text-republic-700 hover:underline sm:inline dark:text-republic-400">{{ __('ui.dashboard.view_all') }} &rarr;</a>
            </x-slot:action>
            <div class="-mx-5 overflow-x-auto sm:-mx-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-y border-sand-200 bg-sand-50 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 dark:border-sand-800 dark:bg-sand-950 dark:text-sand-400">
                            <th scope="col" class="py-3 pr-2 pl-5 sm:px-6">{{ __('ui.budget.party') }}</th>
                            <th scope="col" class="px-2 py-3 text-right sm:px-4">{{ __('ui.dashboard.initiatives_column') }}</th>
                            <th scope="col" class="px-2 py-3 sm:w-1/4 sm:px-4">{{ __('ui.dashboard.approved_column') }}</th>
                            <th scope="col" class="py-3 pr-5 pl-2 sm:w-1/3 sm:px-6">{{ __('ui.dashboard.votes_column') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand-100 dark:divide-sand-800">
                        @foreach ($partyTable as $party)
                            <tr class="transition-colors hover:bg-sand-50 dark:hover:bg-sand-950">
                                <th scope="row" class="py-3 pr-2 pl-5 text-left sm:px-6">
                                    <a href="{{ $legislatureContext->route('parties.show', strtolower($party['acronym'])) }}"
                                       class="inline-flex justify-center whitespace-nowrap rounded-lg bg-sand-100 px-2.5 py-1 text-sm font-bold text-sand-900 transition-colors hover:bg-republic-100 hover:text-republic-800 sm:min-w-14 dark:bg-sand-800 dark:text-sand-100 dark:hover:bg-republic-950 dark:hover:text-republic-300">{{ $party['acronym'] }}</a>
                                </th>
                                <td class="px-2 py-3 text-right font-serif text-base font-bold tabular-nums text-sand-900 sm:px-4 sm:text-lg dark:text-sand-100">{{ number_format($party['initiatives'], 0, ',', '.') }}</td>
                                <td class="px-2 py-3 sm:px-4">
                                    @if ($party['initiatives'] > 0)
                                        <div class="flex items-baseline justify-between gap-2">
                                            <span class="font-semibold tabular-nums text-republic-700 dark:text-republic-400">{{ number_format($party['approval_rate'], 1, ',', '.') }}%</span>
                                            <span class="hidden text-xs tabular-nums text-sand-500 sm:inline dark:text-sand-400">{{ __('ui.budget.of_count', ['part' => number_format($party['approved'], 0, ',', '.'), 'total' => number_format($party['initiatives'], 0, ',', '.')]) }}</span>
                                        </div>
                                        <x-progress-bar :share="$party['approval_rate'] / 100" class="mt-1.5" />
                                    @else
                                        <span class="text-sand-400 dark:text-sand-600">—</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-5 pl-2 sm:px-6">
                                    @if ($party['total_votes'] > 0)
                                        <div class="flex items-baseline justify-between gap-2">
                                            <span class="font-semibold tabular-nums text-sand-800 dark:text-sand-200">{{ __('ui.budget.articles.favor_rate', ['percent' => number_format($party['favor_pct'], 0, ',', '.')]) }}</span>
                                            <span class="hidden text-xs tabular-nums text-sand-500 sm:inline dark:text-sand-400">{{ trans_choice('ui.dashboard.votes_count', $party['total_votes'], ['count' => number_format($party['total_votes'], 0, ',', '.')]) }}</span>
                                        </div>
                                        <x-share-bar :counts="$party['votes']" class="mt-1.5" title="{{ __('ui.budget.articles.share_title', $party['votes']) }}" />
                                    @else
                                        <span class="text-sand-400 dark:text-sand-600">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <ul class="mt-4 flex flex-wrap items-center gap-4 text-xs text-sand-500 dark:text-sand-400">
                <li class="flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-full bg-republic-500"></span>{{ __('ui.position.favor') }}</li>
                <li class="flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-full bg-parliament-500"></span>{{ __('ui.position.contra') }}</li>
                <li class="flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-full bg-amber-400"></span>{{ __('ui.position.abstencao') }}</li>
            </ul>
        </x-section-panel>

        {{-- Latest votes --}}
        <x-section-panel id="latest-title" :title="__('ui.dashboard.latest_votes')" :intro="__('ui.dashboard.latest_intro')" icon="chat">
            <x-slot:action>
                <a href="{{ $legislatureContext->route('initiatives.index') }}" class="hidden text-sm font-semibold text-republic-700 hover:underline sm:inline dark:text-republic-400">{{ __('ui.dashboard.view_all') }} &rarr;</a>
            </x-slot:action>
            <div class="-mx-5 -mb-5 overflow-hidden rounded-b-2xl sm:-mx-6 sm:-mb-6">
                @if ($latestInitiatives->isEmpty())
                    <p class="py-8 text-center text-sm text-sand-500 dark:text-sand-400">
                        {{ __('ui.dashboard.no_votes') }}
                    </p>
                @else
                    {{-- Mobile: card layout --}}
                    <div class="divide-y divide-sand-100 dark:divide-sand-800 sm:hidden">
                        @foreach ($latestInitiatives as $initiative)
                            <div class="space-y-2 px-4 py-3">
                                <div class="flex items-start justify-between gap-2">
                                    <a href="{{ route('initiatives.show', $initiative->id) }}"
                                       class="min-w-0 truncate text-sm font-medium text-sand-900 dark:text-sand-100">
                                        {{ str($initiative->title)->limit(75) }}
                                    </a>
                                    <x-status-badge :status="$initiative->status" size="xs" class="shrink-0" />
                                </div>
                                <div class="flex items-center justify-between text-xs text-sand-500 dark:text-sand-400">
                                    <span class="truncate">
                                        @if ($initiative->author_category === 'government')
                                            <x-government-badge />
                                        @else
                                            {{ $initiative->author_label ?? __('ui.author_type.Other') }}
                                        @endif
                                    </span>
                                    <span class="shrink-0">{{ $initiative->final_vote_date->format('d/m/Y') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop: table layout --}}
                    <div class="hidden sm:block">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-sand-100 bg-sand-50 dark:border-sand-800 dark:bg-sand-950">
                                <tr>
                                    <th class="px-6 py-3 font-medium text-sand-500 dark:text-sand-400">{{ __('ui.table.initiative') }}</th>
                                    <th class="px-6 py-3 font-medium text-sand-500 dark:text-sand-400">{{ __('ui.table.status') }}</th>
                                    <th class="px-6 py-3 font-medium text-sand-500 dark:text-sand-400">{{ __('ui.table.author') }}</th>
                                    <th class="px-6 py-3 font-medium text-sand-500 dark:text-sand-400">{{ __('ui.table.date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-sand-100 dark:divide-sand-800">
                                @foreach ($latestInitiatives as $initiative)
                                    <tr class="transition-colors hover:bg-sand-50 dark:hover:bg-sand-800/50">
                                        <td class="max-w-md px-6 py-4">
                                            <a href="{{ route('initiatives.show', $initiative->id) }}"
                                               class="font-medium text-republic-600 hover:underline dark:text-republic-400">
                                                {{ str($initiative->title)->limit(75) }}
                                            </a>
                                            <p class="mt-0.5 truncate text-xs text-sand-500 dark:text-sand-400">{{ $initiative->id }}</p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <x-status-badge :status="$initiative->status" />
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($initiative->author_category === 'government')
                                                <x-government-badge />
                                            @else
                                                <span class="text-sand-600 dark:text-sand-400">{{ $initiative->author_label ?? __('ui.author_type.Other') }}</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sand-600 dark:text-sand-400">
                                            {{ $initiative->final_vote_date->format('d/m/Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </x-section-panel>
    </div>
@endsection
