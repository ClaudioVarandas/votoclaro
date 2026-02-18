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

        {{-- Metric cards --}}
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
            </x-metric-card>

            <x-metric-card :title="__('ui.dashboard.government_initiatives')" :value="number_format($governmentStats->total)">
                <x-slot:icon>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sand-400 dark:text-sand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
                    </svg>
                </x-slot:icon>
                @if ($governmentStats->total > 0)
                    <p class="text-sm font-medium text-sand-600 dark:text-sand-400">
                        {{ round(($governmentStats->approved / $governmentStats->total) * 100, 1) }}% {{ mb_strtolower(__('ui.status.approved')) }}
                        &middot;
                        {{ __('ui.days_average', ['count' => round($governmentStats->avg_days ?? 0)]) }}
                    </p>
                @endif
            </x-metric-card>
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
                                <p class="min-w-0 truncate text-sm font-medium text-sand-900 dark:text-sand-100">{{ $initiative->title }}</p>
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
                                        <p class="truncate font-medium text-sand-900 dark:text-sand-100">{{ $initiative->title }}</p>
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
