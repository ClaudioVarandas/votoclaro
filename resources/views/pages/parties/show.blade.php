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
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 15h2.25m8.024-9.75c.011.05.028.1.052.148.591 1.2.924 2.55.924 3.977a8.96 8.96 0 01-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398-.306.774-1.086 1.227-1.918 1.227h-1.053c-.472 0-.745-.556-.5-.96a8.95 8.95 0 001.302-4.665c0-1.194-.232-2.333-.654-3.375z" />
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-wider">{{ __('ui.parties.contra_pct') }}</span>
                </div>
                <p class="mt-2 font-serif text-2xl font-bold text-sand-900 dark:text-sand-100">{{ $stats['contra_pct'] }}%</p>
            </div>

            {{-- Abstention % --}}
            <div class="rounded-2xl border border-sand-200 bg-white p-5 dark:border-sand-800 dark:bg-sand-900">
                <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
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
