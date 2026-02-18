@extends('layouts.app')

<x-seo-meta
    :title="$stats['acronym'] . ' — VotoClaro'"
    :description="__('ui.parties.description')"
/>

@section('content')
    <div class="space-y-8">
        {{-- Back link --}}
        <a href="{{ route('parties.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-sand-500 transition-colors hover:text-republic-700 dark:text-sand-400 dark:hover:text-republic-400">
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            {{ __('ui.parties.back_to_list') }}
        </a>

        {{-- Header --}}
        <div>
            <h1 class="font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">
                {{ $stats['acronym'] }}
            </h1>
        </div>

        {{-- Metric cards --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            {{-- Total votes --}}
            <div class="rounded-2xl border border-sand-200 bg-white p-5 dark:border-sand-800 dark:bg-sand-900">
                <div class="flex items-center gap-2 text-sand-500 dark:text-sand-400">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-wider">{{ __('ui.parties.total_votes') }}</span>
                </div>
                <p class="mt-2 font-serif text-2xl font-bold text-sand-900 dark:text-sand-100">{{ $stats['total_votes'] }}</p>
            </div>

            {{-- Favor % --}}
            <div class="rounded-2xl border border-sand-200 bg-white p-5 dark:border-sand-800 dark:bg-sand-900">
                <div class="flex items-center gap-2 text-republic-600 dark:text-republic-400">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3.25a.75.75 0 01.75-.75 2.25 2.25 0 012.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48a4.53 4.53 0 01-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23H5.904M14.25 9h2.25M5.904 18.75c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 01-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 10.203 4.167 9.75 5 9.75h1.053c.472 0 .745.556.5.96a8.958 8.958 0 00-1.302 4.665c0 1.194.232 2.333.654 3.375z" />
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-wider">{{ __('ui.parties.favor_pct') }}</span>
                </div>
                <p class="mt-2 font-serif text-2xl font-bold text-sand-900 dark:text-sand-100">{{ $stats['favor_pct'] }}%</p>
            </div>

            {{-- Contra % --}}
            <div class="rounded-2xl border border-sand-200 bg-white p-5 dark:border-sand-800 dark:bg-sand-900">
                <div class="flex items-center gap-2 text-parliament-600 dark:text-parliament-400">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.498 15.25H4.372c-1.026 0-1.945-.694-2.054-1.715a12.137 12.137 0 0 1-.068-1.285c0-2.848.992-5.464 2.649-7.521C5.287 4.247 5.886 4 6.504 4h4.016a4.5 4.5 0 0 1 1.423.23l3.114 1.04a4.5 4.5 0 0 0 1.423.23h1.294M7.498 15.25c.618 0 .991.724.725 1.282A7.471 7.471 0 0 0 7.5 19.75 2.25 2.25 0 0 0 9.75 22a.75.75 0 0 0 .75-.75v-.633c0-.573.11-1.14.322-1.672.304-.76.93-1.33 1.653-1.715a9.04 9.04 0 0 0 2.86-2.4c.498-.634 1.226-1.08 2.032-1.08h.384m-10.253 1.5H9.7m8.075-9.75c.01.05.027.1.05.148.593 1.2.925 2.55.925 3.977 0 1.487-.36 2.89-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398-.306.774-1.086 1.227-1.918 1.227h-1.053c-.472 0-.745-.556-.5-.96a8.95 8.95 0 0 0 .303-.54" />
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-wider">{{ __('ui.parties.contra_pct') }}</span>
                </div>
                <p class="mt-2 font-serif text-2xl font-bold text-sand-900 dark:text-sand-100">{{ $stats['contra_pct'] }}%</p>
            </div>

            {{-- Abstention % --}}
            <div class="rounded-2xl border border-sand-200 bg-white p-5 dark:border-sand-800 dark:bg-sand-900">
                <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-wider">{{ __('ui.parties.abstencao_pct') }}</span>
                </div>
                <p class="mt-2 font-serif text-2xl font-bold text-sand-900 dark:text-sand-100">{{ $stats['abstencao_pct'] }}%</p>
            </div>
        </div>

        {{-- Vote summary bar --}}
        @if ($stats['total_votes'] > 0)
            <div class="rounded-2xl border border-sand-200 bg-white p-6 dark:border-sand-800 dark:bg-sand-900">
                <x-vote-summary-bar :counts="['favor' => $stats['favor'], 'contra' => $stats['contra'], 'abstencao' => $stats['abstencao']]" />
            </div>
        @endif

        {{-- Government alignment --}}
        <div class="rounded-2xl border border-sand-200 bg-white p-6 dark:border-sand-800 dark:bg-sand-900">
            <h2 class="font-serif text-lg font-semibold text-sand-900 dark:text-sand-100">
                {{ __('ui.parties.government_alignment') }}
            </h2>
            <p class="mt-1 text-sm text-sand-500 dark:text-sand-400">
                {{ __('ui.parties.government_alignment_description') }}
            </p>
            <div class="mt-4">
                <div class="flex items-end gap-2">
                    <span class="font-serif text-3xl font-bold text-sand-900 dark:text-sand-100">{{ $stats['government_alignment'] }}%</span>
                </div>
                <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-sand-200 dark:bg-sand-800">
                    <div class="h-full rounded-full bg-republic-500 transition-all" style="width: {{ $stats['government_alignment'] }}%"></div>
                </div>
            </div>
        </div>

        @php
            $partySlug = strtolower($stats['acronym']);
        @endphp

        {{-- Authored initiatives --}}
        <div id="authored-initiatives" x-data="{ showAuthored: true }" class="rounded-2xl border border-sand-200 bg-white p-6 dark:border-sand-800 dark:bg-sand-900">
            <button @click="showAuthored = !showAuthored" class="flex w-full items-center justify-between">
                <h2 class="font-serif text-lg font-semibold text-sand-900 dark:text-sand-100">
                    {{ __('ui.parties.authored_initiatives') }}
                </h2>
                <span class="text-sm text-republic-600 dark:text-republic-400" x-text="showAuthored ? '{{ __('ui.parties.hide_authored') }}' : '{{ __('ui.parties.show_authored') }}'"></span>
            </button>

            <div x-show="showAuthored" x-transition class="mt-4">
                @if ($authoredStatusCounts['total'] === 0 && !$authoredCurrentStatus && !$authoredCurrentType && !$authoredCurrentSearch)
                    <p class="py-4 text-center text-sm text-sand-500 dark:text-sand-400">
                        {{ __('ui.parties.authored_empty') }}
                    </p>
                @else
                    {{-- Mini-metric cards --}}
                    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-xl border border-sand-200 bg-sand-50 px-4 py-3 dark:border-sand-700 dark:bg-sand-800">
                            <span class="text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.parties.authored_total') }}</span>
                            <p class="mt-1 font-serif text-xl font-bold text-sand-900 dark:text-sand-100">{{ $authoredStatusCounts['total'] }}</p>
                        </div>
                        <div class="rounded-xl border border-sand-200 bg-sand-50 px-4 py-3 dark:border-sand-700 dark:bg-sand-800">
                            <span class="text-xs font-semibold uppercase tracking-wider text-republic-600 dark:text-republic-400">{{ __('ui.parties.authored_approved') }}</span>
                            <p class="mt-1 font-serif text-xl font-bold text-sand-900 dark:text-sand-100">{{ $authoredStatusCounts['approved'] }}</p>
                        </div>
                        <div class="rounded-xl border border-sand-200 bg-sand-50 px-4 py-3 dark:border-sand-700 dark:bg-sand-800">
                            <span class="text-xs font-semibold uppercase tracking-wider text-parliament-600 dark:text-parliament-400">{{ __('ui.parties.authored_rejected') }}</span>
                            <p class="mt-1 font-serif text-xl font-bold text-sand-900 dark:text-sand-100">{{ $authoredStatusCounts['rejected'] }}</p>
                        </div>
                        <div class="rounded-xl border border-sand-200 bg-sand-50 px-4 py-3 dark:border-sand-700 dark:bg-sand-800">
                            <span class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ __('ui.parties.authored_in_progress') }}</span>
                            <p class="mt-1 font-serif text-xl font-bold text-sand-900 dark:text-sand-100">{{ $authoredStatusCounts['in_progress'] }}</p>
                        </div>
                    </div>

                    {{-- Filters --}}
                    @php
                        $authoredAnchor = '#authored-initiatives';
                        $authoredTabBaseParams = array_filter([
                            'authored_type' => $authoredCurrentType,
                            'authored_sort' => $authoredCurrentSort !== 'date' ? $authoredCurrentSort : null,
                            'authored_direction' => $authoredCurrentDirection !== 'desc' ? $authoredCurrentDirection : null,
                            'position' => $currentPosition,
                            'type' => $currentType,
                            'sort' => $currentSort !== 'date' ? $currentSort : null,
                            'direction' => $currentDirection !== 'desc' ? $currentDirection : null,
                        ]);
                    @endphp

                    <div class="mb-4 flex flex-wrap items-center gap-3 sm:flex-nowrap">
                        {{-- Status filter tabs --}}
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('parties.show', array_merge([$partySlug], $authoredTabBaseParams)) . $authoredAnchor }}"
                               @class([
                                   'rounded-full px-3 py-1 text-sm font-medium transition-colors',
                                   'bg-republic-600 text-white dark:bg-republic-500' => $authoredCurrentStatus === null,
                                   'bg-sand-100 text-sand-700 hover:bg-sand-200 dark:bg-sand-800 dark:text-sand-300 dark:hover:bg-sand-700' => $authoredCurrentStatus !== null,
                               ])>
                                {{ __('ui.parties.authored_filter_all') }} ({{ $authoredStatusCounts['total'] }})
                            </a>

                            @foreach (['approved', 'rejected', 'in_progress'] as $status)
                                <a href="{{ route('parties.show', array_merge([$partySlug, 'authored_status' => $status], $authoredTabBaseParams)) . $authoredAnchor }}"
                                   @class([
                                       'rounded-full px-3 py-1 text-sm font-medium transition-colors',
                                       'bg-republic-600 text-white dark:bg-republic-500' => $authoredCurrentStatus === $status,
                                       'bg-sand-100 text-sand-700 hover:bg-sand-200 dark:bg-sand-800 dark:text-sand-300 dark:hover:bg-sand-700' => $authoredCurrentStatus !== $status,
                                   ])>
                                    {{ __('ui.status.' . $status) }} ({{ $authoredStatusCounts[$status] }})
                                </a>
                            @endforeach
                        </div>

                        {{-- Type filter dropdown --}}
                        @php
                            $authoredTypeBaseParams = array_filter([
                                'authored_status' => $authoredCurrentStatus,
                                'authored_sort' => $authoredCurrentSort !== 'date' ? $authoredCurrentSort : null,
                                'authored_direction' => $authoredCurrentDirection !== 'desc' ? $authoredCurrentDirection : null,
                                'position' => $currentPosition,
                                'type' => $currentType,
                                'sort' => $currentSort !== 'date' ? $currentSort : null,
                                'direction' => $currentDirection !== 'desc' ? $currentDirection : null,
                            ]);
                        @endphp
                        <select
                            onchange="if(this.value){window.location=this.value}"
                            class="rounded-lg border border-sand-200 bg-white px-3 py-1.5 text-sm text-sand-700 dark:border-sand-700 dark:bg-sand-800 dark:text-sand-300">
                            <option value="{{ route('parties.show', array_merge([$partySlug], $authoredTypeBaseParams)) . $authoredAnchor }}" {{ $authoredCurrentType === null ? 'selected' : '' }}>
                                {{ __('ui.parties.authored_all_types') }}
                            </option>
                            @foreach ($authoredTypes as $typeCode => $typeLabel)
                                <option value="{{ route('parties.show', array_merge([$partySlug, 'authored_type' => $typeCode], $authoredTypeBaseParams)) . $authoredAnchor }}" {{ $authoredCurrentType === $typeCode ? 'selected' : '' }}>
                                    {{ $typeLabel }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Search input --}}
                        <form action="{{ route('parties.show', $partySlug) . $authoredAnchor }}" method="GET" class="ml-auto">
                            @if ($authoredCurrentStatus)
                                <input type="hidden" name="authored_status" value="{{ $authoredCurrentStatus }}">
                            @endif
                            @if ($authoredCurrentType)
                                <input type="hidden" name="authored_type" value="{{ $authoredCurrentType }}">
                            @endif
                            @if ($authoredCurrentSort !== 'date')
                                <input type="hidden" name="authored_sort" value="{{ $authoredCurrentSort }}">
                            @endif
                            @if ($authoredCurrentDirection !== 'desc')
                                <input type="hidden" name="authored_direction" value="{{ $authoredCurrentDirection }}">
                            @endif
                            @if ($currentPosition)
                                <input type="hidden" name="position" value="{{ $currentPosition }}">
                            @endif
                            @if ($currentType)
                                <input type="hidden" name="type" value="{{ $currentType }}">
                            @endif
                            @if ($currentSort !== 'date')
                                <input type="hidden" name="sort" value="{{ $currentSort }}">
                            @endif
                            @if ($currentDirection !== 'desc')
                                <input type="hidden" name="direction" value="{{ $currentDirection }}">
                            @endif
                            <div class="relative">
                                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-sand-400 dark:text-sand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                <input
                                    type="text"
                                    name="authored_search"
                                    value="{{ $authoredCurrentSearch }}"
                                    placeholder="{{ __('ui.parties.authored_search') }}"
                                    class="w-48 rounded-lg border border-sand-200 bg-white py-1.5 pl-8 pr-3 text-sm text-sand-700 placeholder-sand-400 dark:border-sand-700 dark:bg-sand-800 dark:text-sand-300 dark:placeholder-sand-500 sm:w-56"
                                />
                            </div>
                        </form>
                    </div>

                    {{-- Desktop table --}}
                    <div class="hidden sm:block">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-sand-200 dark:border-sand-700">
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">
                                        {{ __('ui.table.initiative') }}
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">
                                        {{ __('ui.initiatives.type') }}
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">
                                        @php
                                            $authoredDateDirection = ($authoredCurrentSort === 'date' && $authoredCurrentDirection === 'desc') ? 'asc' : 'desc';
                                            $authoredDateSortParams = array_filter([
                                                'authored_status' => $authoredCurrentStatus,
                                                'authored_type' => $authoredCurrentType,
                                                'authored_sort' => 'date',
                                                'authored_direction' => $authoredDateDirection,
                                                'position' => $currentPosition,
                                                'type' => $currentType,
                                                'sort' => $currentSort !== 'date' ? $currentSort : null,
                                                'direction' => $currentDirection !== 'desc' ? $currentDirection : null,
                                            ]);
                                        @endphp
                                        <a href="{{ route('parties.show', array_merge([$partySlug], $authoredDateSortParams)) . $authoredAnchor }}"
                                           class="inline-flex items-center gap-1 hover:text-sand-900 dark:hover:text-sand-100">
                                            {{ __('ui.parties.authored_sort_date') }}
                                            @if ($authoredCurrentSort === 'date')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    @if ($authoredCurrentDirection === 'asc')
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                    @endif
                                                </svg>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">
                                        @php
                                            $authoredStatusDirection = ($authoredCurrentSort === 'status' && $authoredCurrentDirection === 'desc') ? 'asc' : 'desc';
                                            $authoredStatusSortParams = array_filter([
                                                'authored_status' => $authoredCurrentStatus,
                                                'authored_type' => $authoredCurrentType,
                                                'authored_sort' => 'status',
                                                'authored_direction' => $authoredStatusDirection,
                                                'position' => $currentPosition,
                                                'type' => $currentType,
                                                'sort' => $currentSort !== 'date' ? $currentSort : null,
                                                'direction' => $currentDirection !== 'desc' ? $currentDirection : null,
                                            ]);
                                        @endphp
                                        <a href="{{ route('parties.show', array_merge([$partySlug], $authoredStatusSortParams)) . $authoredAnchor }}"
                                           class="inline-flex items-center gap-1 hover:text-sand-900 dark:hover:text-sand-100">
                                            {{ __('ui.parties.authored_sort_status') }}
                                            @if ($authoredCurrentSort === 'status')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    @if ($authoredCurrentDirection === 'asc')
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                    @endif
                                                </svg>
                                            @endif
                                        </a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-sand-100 dark:divide-sand-800">
                                @foreach ($authoredInitiatives as $initiative)
                                    <tr class="transition-colors hover:bg-sand-50 dark:hover:bg-sand-800/50">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('initiatives.show', $initiative->id) }}"
                                               class="font-medium text-republic-600 hover:underline dark:text-republic-400">
                                                {{ str($initiative->title)->limit(80) }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sand-600 dark:text-sand-400">
                                            {{ $initiative->initiative_type_label }}
                                        </td>
                                        <td class="px-4 py-3 text-sand-600 dark:text-sand-400">
                                            {{ $initiative->entry_date?->format('d M Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span @class([
                                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                                'bg-republic-100 text-republic-800 dark:bg-republic-950 dark:text-republic-300' => $initiative->status === 'approved',
                                                'bg-parliament-100 text-parliament-800 dark:bg-parliament-950 dark:text-parliament-300' => $initiative->status === 'rejected',
                                                'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' => $initiative->status === 'in_progress',
                                            ])>
                                                {{ __('ui.status.' . $initiative->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile stacked cards --}}
                    <div class="space-y-3 sm:hidden">
                        @foreach ($authoredInitiatives as $initiative)
                            <div class="rounded-xl border border-sand-200 p-4 dark:border-sand-700">
                                <a href="{{ route('initiatives.show', $initiative->id) }}"
                                   class="font-medium text-republic-600 hover:underline dark:text-republic-400">
                                    {{ str($initiative->title)->limit(100) }}
                                </a>
                                <dl class="mt-2 grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.initiatives.type') }}</dt>
                                        <dd class="mt-0.5 text-sand-700 dark:text-sand-300">{{ $initiative->initiative_type_label }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.parties.authored_sort_date') }}</dt>
                                        <dd class="mt-0.5 text-sand-700 dark:text-sand-300">{{ $initiative->entry_date?->format('d M Y') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.parties.authored_sort_status') }}</dt>
                                        <dd class="mt-0.5">
                                            <span @class([
                                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                                'bg-republic-100 text-republic-800 dark:bg-republic-950 dark:text-republic-300' => $initiative->status === 'approved',
                                                'bg-parliament-100 text-parliament-800 dark:bg-parliament-950 dark:text-parliament-300' => $initiative->status === 'rejected',
                                                'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' => $initiative->status === 'in_progress',
                                            ])>
                                                {{ __('ui.status.' . $initiative->status) }}
                                            </span>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if ($authoredInitiatives->hasPages())
                        <div class="mt-4">
                            {{ $authoredInitiatives->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Voting history --}}
        <div id="voting-history" x-data="{ showHistory: true }" class="rounded-2xl border border-sand-200 bg-white p-6 dark:border-sand-800 dark:bg-sand-900">
            <button @click="showHistory = !showHistory" class="flex w-full items-center justify-between">
                <h2 class="font-serif text-lg font-semibold text-sand-900 dark:text-sand-100">
                    {{ __('ui.parties.voting_history') }}
                </h2>
                <span class="text-sm text-republic-600 dark:text-republic-400" x-text="showHistory ? '{{ __('ui.parties.hide_voting_history') }}' : '{{ __('ui.parties.show_voting_history') }}'"></span>
            </button>

            <div x-show="showHistory" x-transition class="mt-4">
                @if ($positionCounts['total'] === 0)
                    <p class="py-4 text-center text-sm text-sand-500 dark:text-sand-400">
                        {{ __('ui.parties.voting_history_empty') }}
                    </p>
                @else
                    {{-- Filters --}}
                    <div class="mb-4 flex flex-wrap items-center gap-3 sm:flex-nowrap">
                        @php
                            $anchor = '#voting-history';
                            $tabBaseParams = array_filter([
                                'type' => $currentType,
                                'sort' => $currentSort !== 'date' ? $currentSort : null,
                                'direction' => $currentDirection !== 'desc' ? $currentDirection : null,
                            ]);
                        @endphp

                        {{-- Position filter tabs --}}
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('parties.show', array_merge([$partySlug], $tabBaseParams)) . $anchor }}"
                               @class([
                                   'rounded-full px-3 py-1 text-sm font-medium transition-colors',
                                   'bg-republic-600 text-white dark:bg-republic-500' => $currentPosition === null,
                                   'bg-sand-100 text-sand-700 hover:bg-sand-200 dark:bg-sand-800 dark:text-sand-300 dark:hover:bg-sand-700' => $currentPosition !== null,
                               ])>
                                {{ __('ui.parties.filter_all') }} ({{ $positionCounts['total'] }})
                            </a>

                            @foreach (['favor', 'contra', 'abstencao'] as $pos)
                                <a href="{{ route('parties.show', array_merge([$partySlug, 'position' => $pos], $tabBaseParams)) . $anchor }}"
                                   @class([
                                       'rounded-full px-3 py-1 text-sm font-medium transition-colors',
                                       'bg-republic-600 text-white dark:bg-republic-500' => $currentPosition === $pos,
                                       'bg-sand-100 text-sand-700 hover:bg-sand-200 dark:bg-sand-800 dark:text-sand-300 dark:hover:bg-sand-700' => $currentPosition !== $pos,
                                   ])>
                                    {{ __('ui.position.' . $pos) }} ({{ $positionCounts[$pos] }})
                                </a>
                            @endforeach
                        </div>

                        {{-- Type filter dropdown --}}
                        @php
                            $typeBaseParams = array_filter([
                                'position' => $currentPosition,
                                'sort' => $currentSort !== 'date' ? $currentSort : null,
                                'direction' => $currentDirection !== 'desc' ? $currentDirection : null,
                            ]);
                        @endphp
                        <select
                            onchange="if(this.value){window.location=this.value}"
                            class="rounded-lg border border-sand-200 bg-white px-3 py-1.5 text-sm text-sand-700 dark:border-sand-700 dark:bg-sand-800 dark:text-sand-300">
                            <option value="{{ route('parties.show', array_merge([$partySlug], $typeBaseParams)) . $anchor }}" {{ $currentType === null ? 'selected' : '' }}>
                                {{ __('ui.parties.all_types') }}
                            </option>
                            @foreach ($initiativeTypes as $typeCode => $typeLabel)
                                <option value="{{ route('parties.show', array_merge([$partySlug, 'type' => $typeCode], $typeBaseParams)) . $anchor }}" {{ $currentType === $typeCode ? 'selected' : '' }}>
                                    {{ $typeLabel }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Search input --}}
                        <form action="{{ route('parties.show', $partySlug) . $anchor }}" method="GET" class="ml-auto">
                            @if ($currentPosition)
                                <input type="hidden" name="position" value="{{ $currentPosition }}">
                            @endif
                            @if ($currentType)
                                <input type="hidden" name="type" value="{{ $currentType }}">
                            @endif
                            @if ($currentSort !== 'date')
                                <input type="hidden" name="sort" value="{{ $currentSort }}">
                            @endif
                            @if ($currentDirection !== 'desc')
                                <input type="hidden" name="direction" value="{{ $currentDirection }}">
                            @endif
                            <div class="relative">
                                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-sand-400 dark:text-sand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                <input
                                    type="text"
                                    name="search"
                                    value="{{ $currentSearch }}"
                                    placeholder="{{ __('ui.parties.search_initiatives') }}"
                                    class="w-48 rounded-lg border border-sand-200 bg-white py-1.5 pl-8 pr-3 text-sm text-sand-700 placeholder-sand-400 dark:border-sand-700 dark:bg-sand-800 dark:text-sand-300 dark:placeholder-sand-500 sm:w-56"
                                />
                            </div>
                        </form>
                    </div>

                    {{-- Desktop table --}}
                    <div class="hidden sm:block">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-sand-200 dark:border-sand-700">
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">
                                        {{ __('ui.table.initiative') }}
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">
                                        {{ __('ui.initiatives.type') }}
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">
                                        @php
                                            $dateDirection = ($currentSort === 'date' && $currentDirection === 'desc') ? 'asc' : 'desc';
                                        @endphp
                                        <a href="{{ route('parties.show', array_merge([strtolower($stats['acronym'])], array_filter(['position' => $currentPosition, 'type' => $currentType, 'sort' => 'date', 'direction' => $dateDirection]))) . $anchor }}"
                                           class="inline-flex items-center gap-1 hover:text-sand-900 dark:hover:text-sand-100">
                                            {{ __('ui.parties.sort_date') }}
                                            @if ($currentSort === 'date')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    @if ($currentDirection === 'asc')
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                    @endif
                                                </svg>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">
                                        {{ __('ui.table.position') }}
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">
                                        @php
                                            $resultDirection = ($currentSort === 'result' && $currentDirection === 'desc') ? 'asc' : 'desc';
                                        @endphp
                                        <a href="{{ route('parties.show', array_merge([strtolower($stats['acronym'])], array_filter(['position' => $currentPosition, 'type' => $currentType, 'sort' => 'result', 'direction' => $resultDirection]))) . $anchor }}"
                                           class="inline-flex items-center gap-1 hover:text-sand-900 dark:hover:text-sand-100">
                                            {{ __('ui.parties.sort_result') }}
                                            @if ($currentSort === 'result')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    @if ($currentDirection === 'asc')
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                    @endif
                                                </svg>
                                            @endif
                                        </a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-sand-100 dark:divide-sand-800">
                                @foreach ($votes as $votePosition)
                                    <tr class="transition-colors hover:bg-sand-50 dark:hover:bg-sand-800/50">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('initiatives.show', $votePosition->vote->initiative->id) }}"
                                               class="font-medium text-republic-600 hover:underline dark:text-republic-400">
                                                {{ str($votePosition->vote->initiative->title)->limit(80) }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sand-600 dark:text-sand-400">
                                            {{ $votePosition->vote->initiative->initiative_type_label }}
                                        </td>
                                        <td class="px-4 py-3 text-sand-600 dark:text-sand-400">
                                            {{ $votePosition->vote->initiative->final_vote_date?->format('d M Y') ?? __('ui.parties.date_pending') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-position-badge :position="$votePosition->position" />
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($votePosition->vote->result)
                                                <x-vote-result-badge :result="$votePosition->vote->result" />
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile stacked cards --}}
                    <div class="space-y-3 sm:hidden">
                        @foreach ($votes as $votePosition)
                            <div class="rounded-xl border border-sand-200 p-4 dark:border-sand-700">
                                <a href="{{ route('initiatives.show', $votePosition->vote->initiative->id) }}"
                                   class="font-medium text-republic-600 hover:underline dark:text-republic-400">
                                    {{ str($votePosition->vote->initiative->title)->limit(100) }}
                                </a>
                                <dl class="mt-2 grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.initiatives.type') }}</dt>
                                        <dd class="mt-0.5 text-sand-700 dark:text-sand-300">{{ $votePosition->vote->initiative->initiative_type_label }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.parties.sort_date') }}</dt>
                                        <dd class="mt-0.5 text-sand-700 dark:text-sand-300">{{ $votePosition->vote->initiative->final_vote_date?->format('d M Y') ?? __('ui.parties.date_pending') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.table.position') }}</dt>
                                        <dd class="mt-0.5"><x-position-badge :position="$votePosition->position" /></dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.parties.sort_result') }}</dt>
                                        <dd class="mt-0.5">
                                            @if ($votePosition->vote->result)
                                                <x-vote-result-badge :result="$votePosition->vote->result" />
                                            @endif
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if ($votes->hasPages())
                        <div class="mt-4">
                            {{ $votes->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Monthly trend --}}
        @if (count($trend) > 0)
            <div x-data="{ showTrend: false }" class="rounded-2xl border border-sand-200 bg-white p-6 dark:border-sand-800 dark:bg-sand-900">
                <button @click="showTrend = !showTrend" class="flex w-full items-center justify-between">
                    <h2 class="font-serif text-lg font-semibold text-sand-900 dark:text-sand-100">
                        {{ __('ui.parties.monthly_trend') }}
                    </h2>
                    <span class="text-sm text-republic-600 dark:text-republic-400" x-text="showTrend ? '{{ __('ui.parties.hide_trend') }}' : '{{ __('ui.parties.show_trend') }}'"></span>
                </button>

                <div x-show="showTrend" x-cloak x-transition class="mt-4">
                    <x-monthly-trend-chart :trend="$trend" />
                </div>
            </div>
        @endif
    </div>
@endsection
