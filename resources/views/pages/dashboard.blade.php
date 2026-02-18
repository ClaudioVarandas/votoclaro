@extends('layouts.app')

@php
    $govApprovalRate = $governmentStats->total > 0
        ? number_format(($governmentStats->approved / $governmentStats->total) * 100, 1, ',', '.')
        : '0';
@endphp

<x-seo-meta
    :title="__('ui.dashboard.title') . ' — VotoClaro'"
    :description="__('ui.dashboard.seo_description', [
        'total' => number_format($totalInitiatives, 0, ',', '.'),
        'approval_rate' => str_replace('.', ',', $approvalRate),
        'gov_rate' => $govApprovalRate,
    ])"
/>

@section('content')
    <div class="space-y-8">
        {{-- Header --}}
        <div>
            <h1 class="font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">
                {{ __('ui.dashboard.title') }}
            </h1>
            <p class="mt-2 text-sand-500 dark:text-sand-400">
                {{ __('ui.dashboard.description') }}
            </p>
        </div>

        {{-- Primary metric cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-metric-card :title="__('ui.dashboard.total_initiatives')" :value="number_format($totalInitiatives)">
                <x-slot:icon>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sand-400 dark:text-sand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </x-slot:icon>
                @php
                    $approvedPct = $totalInitiatives > 0 ? ($approved / $totalInitiatives) * 100 : 0;
                    $rejectedPct = $totalInitiatives > 0 ? ($rejected / $totalInitiatives) * 100 : 0;
                    $inProgressPct = $totalInitiatives > 0 ? ($inProgress / $totalInitiatives) * 100 : 0;
                @endphp
                <div class="space-y-2.5">
                    <div class="space-y-1">
                        <div class="flex items-baseline justify-between">
                            <span class="text-xs text-sand-500 dark:text-sand-400">{{ __('ui.status.approved') }}</span>
                            <span class="text-xs font-semibold text-republic-700 dark:text-republic-300">{{ number_format($approved) }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-sand-200 dark:bg-sand-800">
                            <div class="h-full rounded-full bg-republic-500" style="width: {{ $approvedPct }}%"></div>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-baseline justify-between">
                            <span class="text-xs text-sand-500 dark:text-sand-400">{{ __('ui.status.rejected') }}</span>
                            <span class="text-xs font-semibold text-parliament-700 dark:text-parliament-300">{{ number_format($rejected) }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-sand-200 dark:bg-sand-800">
                            <div class="h-full rounded-full bg-parliament-500" style="width: {{ $rejectedPct }}%"></div>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-baseline justify-between">
                            <span class="text-xs text-sand-500 dark:text-sand-400">{{ __('ui.status.in_progress') }}</span>
                            <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">{{ number_format($inProgress) }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-sand-200 dark:bg-sand-800">
                            <div class="h-full rounded-full bg-amber-400" style="width: {{ $inProgressPct }}%"></div>
                        </div>
                    </div>
                </div>
            </x-metric-card>

            <x-metric-card :title="__('ui.dashboard.approval_rate')" :value="$approvalRate . '%'">
                <x-slot:icon>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sand-400 dark:text-sand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                    </svg>
                </x-slot:icon>
                <div class="h-2 w-full overflow-hidden rounded-full bg-sand-200 dark:bg-sand-800">
                    <div class="h-full rounded-full bg-republic-500" style="width: {{ $approvalRate }}%"></div>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-sand-100 pt-3 dark:border-sand-800">
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sand-400 dark:text-sand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                        <span class="text-sm font-medium text-sand-500 dark:text-sand-400">{{ __('ui.dashboard.unanimous_votes') }}</span>
                    </div>
                    <p class="font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">{{ number_format($unanimousCount) }} <span class="text-xl font-semibold text-sand-500 dark:text-sand-400">({{ $unanimousPct }}%)</span></p>
                </div>
            </x-metric-card>

            <div class="flex flex-col rounded-2xl border border-sand-200 bg-white p-6 dark:border-sand-800 dark:bg-sand-900">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sand-400 dark:text-sand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <p class="text-sm font-medium text-sand-500 dark:text-sand-400">{{ __('ui.dashboard.government_initiatives') }}</p>
                </div>
                <div class="mt-4 space-y-2">
                    <div class="flex items-baseline justify-between">
                        <span class="text-sm text-sand-600 dark:text-sand-400">{{ __('ui.government') }}</span>
                        <span class="font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">{{ number_format($governmentStats->total) }}</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-sm text-sand-600 dark:text-sand-400">{{ __('ui.author_type.Other') }}</span>
                        <span class="font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">{{ number_format($legislationBalance->parl ?? 0) }}</span>
                    </div>
                </div>
                @if ($governmentStats->total > 0)
                    <div class="mt-3 flex flex-1 flex-col justify-end">
                        <p class="text-sm text-sand-600 dark:text-sand-400">
                            <span class="font-serif text-3xl font-bold text-sand-900 dark:text-sand-100">
                                {{ round(($governmentStats->approved / $governmentStats->total) * 100, 1) }}%
                            </span> {{ mb_strtolower(__('ui.dashboard.approval_rate')) }}
                        </p>
                        <p class="text-sm text-sand-600 dark:text-sand-400">
                            <span class="font-serif text-3xl font-bold text-sand-900 dark:text-sand-100">
                                {{ round($governmentStats->avg_days ?? 0) }}
                            </span> {{ __('ui.days_average_unit') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Secondary metric cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Most Active Party --}}
            <x-metric-card :title="__('ui.dashboard.most_active_party')" :value="$mostActiveParties->first() ? $mostActiveParties->first()->author_party . ' — ' . $mostActiveParties->first()->total : '—'">
                <x-slot:icon>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sand-400 dark:text-sand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </x-slot:icon>
                @if ($mostActiveParties->isNotEmpty())
                    @php $maxActive = $mostActiveParties->first()->total; @endphp
                    <div class="space-y-1.5">
                        @foreach ($mostActiveParties as $party)
                            <div class="flex items-center gap-2">
                                <span class="w-10 shrink-0 text-xs font-semibold text-sand-700 dark:text-sand-300">{{ $party->author_party }}</span>
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-sand-200 dark:bg-sand-800">
                                    <div class="h-full rounded-full bg-republic-500" style="width: {{ ($party->total / $maxActive) * 100 }}%"></div>
                                </div>
                                <span class="shrink-0 text-xs tabular-nums text-sand-500 dark:text-sand-400">{{ $party->total }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-metric-card>

            {{-- Least Active Party --}}
            <x-metric-card :title="__('ui.dashboard.least_active_party')" :value="$leastActiveParties->first() ? $leastActiveParties->first()->author_party . ' — ' . $leastActiveParties->first()->total : '—'">
                <x-slot:icon>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sand-400 dark:text-sand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </x-slot:icon>
                @if ($leastActiveParties->isNotEmpty())
                    @php $maxLeast = $leastActiveParties->max('total'); @endphp
                    <div class="space-y-1.5">
                        @foreach ($leastActiveParties as $party)
                            <div class="flex items-center gap-2">
                                <span class="w-10 shrink-0 text-xs font-semibold text-sand-700 dark:text-sand-300">{{ $party->author_party }}</span>
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-sand-200 dark:bg-sand-800">
                                    <div class="h-full rounded-full bg-parliament-500" style="width: {{ ($party->total / $maxLeast) * 100 }}%"></div>
                                </div>
                                <span class="shrink-0 text-xs tabular-nums text-sand-500 dark:text-sand-400">{{ $party->total }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-metric-card>

            {{-- Highest Approval Rate --}}
            <x-metric-card :title="__('ui.dashboard.highest_approval')" :value="$highestApprovalParties->first() ? $highestApprovalParties->first()->author_party . ' — ' . $highestApprovalParties->first()->approval_rate . '%' : '—'">
                <x-slot:icon>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sand-400 dark:text-sand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M18.75 4.236c.982.143 1.954.317 2.916.52A6.003 6.003 0 0016.27 9.728M18.75 4.236V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.023 6.023 0 01-7.54 0" />
                    </svg>
                </x-slot:icon>
                @if ($highestApprovalParties->isNotEmpty())
                    <div class="space-y-1.5">
                        @foreach ($highestApprovalParties as $party)
                            <div class="flex items-center gap-2">
                                <span class="w-10 shrink-0 text-xs font-semibold text-sand-700 dark:text-sand-300">{{ $party->author_party }}</span>
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-sand-200 dark:bg-sand-800">
                                    <div class="h-full rounded-full bg-republic-500" style="width: {{ $party->approval_rate }}%"></div>
                                </div>
                                <span class="shrink-0 text-xs tabular-nums text-sand-500 dark:text-sand-400">{{ $party->approval_rate }}%</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-metric-card>
        </div>

        {{-- Party Quick-Glance Grid --}}
        <div>
            <div class="mb-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-serif text-lg font-semibold text-sand-900 dark:text-sand-100">
                        {{ __('ui.dashboard.party_overview') }}
                    </h2>
                    <a href="{{ route('parties.index') }}" class="text-sm font-medium text-republic-600 transition-colors hover:text-republic-700 dark:text-republic-400 dark:hover:text-republic-300">
                        {{ __('ui.dashboard.view_all') }} &rarr;
                    </a>
                </div>
                <div class="mt-1.5 flex items-center gap-4 text-xs text-sand-500 dark:text-sand-400">
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-republic-500"></span>{{ __('ui.position.favor') }}</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-parliament-500"></span>{{ __('ui.position.contra') }}</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-400"></span>{{ __('ui.position.abstencao') }}</span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
                @foreach ($partyStats as $stats)
                    <a href="{{ route('parties.show', strtolower($stats['acronym'])) }}"
                       class="group rounded-xl border border-sand-200 bg-white p-3 transition-all hover:border-republic-300 hover:shadow-md dark:border-sand-800 dark:bg-sand-900 dark:hover:border-republic-700">
                        <p class="text-center font-serif text-sm font-bold text-sand-900 group-hover:text-republic-700 dark:text-sand-100 dark:group-hover:text-republic-400">
                            {{ $stats['acronym'] }}
                        </p>
                        @if ($stats['total_votes'] > 0)
                            @php
                                $total = $stats['favor'] + $stats['contra'] + $stats['abstencao'];
                                $fPct = $total > 0 ? round(($stats['favor'] / $total) * 100, 1) : 0;
                                $cPct = $total > 0 ? round(($stats['contra'] / $total) * 100, 1) : 0;
                                $aPct = $total > 0 ? 100 - $fPct - $cPct : 0;
                            @endphp
                            <div class="mt-2 flex h-1.5 w-full overflow-hidden rounded-full bg-sand-200 dark:bg-sand-800">
                                @if ($fPct > 0)
                                    <div class="bg-republic-500" style="width: {{ $fPct }}%"></div>
                                @endif
                                @if ($cPct > 0)
                                    <div class="bg-parliament-500" style="width: {{ $cPct }}%"></div>
                                @endif
                                @if ($aPct > 0)
                                    <div class="bg-amber-400" style="width: {{ $aPct }}%"></div>
                                @endif
                            </div>
                            <div class="mt-1.5 flex justify-between text-[10px] tabular-nums text-sand-500 dark:text-sand-400">
                                <span>{{ $fPct }}%</span>
                                <span>{{ $cPct }}%</span>
                                <span>{{ $aPct }}%</span>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Latest votes table --}}
        <div class="overflow-hidden rounded-2xl border border-sand-200 bg-white dark:border-sand-800 dark:bg-sand-900">
            <div class="flex items-center justify-between border-b border-sand-200 px-6 py-4 dark:border-sand-800">
                <h2 class="font-serif text-lg font-semibold text-sand-900 dark:text-sand-100">
                    {{ __('ui.dashboard.latest_votes') }}
                </h2>
                <a href="{{ route('initiatives.index') }}" class="text-sm font-medium text-republic-600 transition-colors hover:text-republic-700 dark:text-republic-400 dark:hover:text-republic-300">
                    {{ __('ui.dashboard.view_all') }} &rarr;
                </a>
            </div>

            @if ($latestInitiatives->isEmpty())
                <p class="px-6 py-8 text-center text-sm text-sand-500 dark:text-sand-400">
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
    </div>
@endsection
