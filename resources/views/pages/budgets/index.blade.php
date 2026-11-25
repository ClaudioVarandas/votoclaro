@extends('layouts.app')

<x-seo-meta
    :title="__('ui.budget.title') . ' — VotoClaro'"
    :description="__('ui.budget.description')"
/>

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">{{ __('ui.budget.title') }}</h1>
            <p class="mt-2 max-w-3xl text-sand-500 dark:text-sand-400">{{ __('ui.budget.description') }}</p>
        </div>

        @if ($budgets->isEmpty())
            <p class="rounded-2xl border border-sand-200 bg-white p-8 text-center text-sm text-sand-500 dark:border-sand-800 dark:bg-sand-900 dark:text-sand-400">
                {{ __('ui.budget.empty') }}
            </p>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($budgets as $item)
                    <a href="{{ $legislatureContext->route('budgets.show', ['year' => $item['budget']->year]) }}"
                       class="group block rounded-2xl border border-sand-200 bg-white p-6 shadow-sm transition-all hover:-translate-y-0.5 hover:border-republic-300 hover:shadow-md dark:border-sand-800 dark:bg-sand-900 dark:hover:border-republic-700">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="font-serif text-xl font-bold text-sand-900 group-hover:text-republic-700 dark:text-sand-100 dark:group-hover:text-republic-400">
                                {{ __('ui.budget.name', ['year' => $item['budget']->year]) }}
                            </h2>
                            @if ($item['closed'])
                                <span class="shrink-0 rounded-full bg-republic-100 px-2 py-0.5 text-xs font-medium text-republic-800 dark:bg-republic-950 dark:text-republic-300">{{ __('ui.budget.vote_closed') }}</span>
                            @else
                                <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-300">{{ __('ui.budget.in_progress') }}</span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm text-sand-500 dark:text-sand-400">
                            {{ __('ui.budget.proposals_count', ['count' => number_format($item['overview']['total'], 0, ',', '.')]) }}
                        </p>
                        <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                            @foreach (['approved', 'rejected', 'pending'] as $key)
                                <x-budget-outcome-stat compact :outcome="$key" :label="__('ui.budget.overview.' . ($key === 'pending' && $item['closed'] ? 'unpublished' : $key))" :value="$item['overview'][$key]" />
                            @endforeach
                        </dl>
                        <p class="mt-4 flex items-center justify-between border-t border-sand-100 pt-4 text-sm font-semibold text-republic-700 dark:border-sand-800 dark:text-republic-400">
                            {{ __('ui.budget.open_budget') }}
                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="size-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
