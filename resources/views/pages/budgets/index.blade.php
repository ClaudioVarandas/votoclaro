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
                       class="group block rounded-2xl border border-sand-200 bg-white p-6 transition-all hover:border-republic-300 hover:shadow-md dark:border-sand-800 dark:bg-sand-900 dark:hover:border-republic-700">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="font-serif text-xl font-bold text-sand-900 group-hover:text-republic-700 dark:text-sand-100 dark:group-hover:text-republic-400">
                                {{ __('ui.budget.name', ['year' => $item['budget']->year]) }}
                            </h2>
                            @if ($item['overview']['pending'] > 0)
                                <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-300">{{ __('ui.budget.in_progress') }}</span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm text-sand-500 dark:text-sand-400">
                            {{ __('ui.budget.proposals_count', ['count' => number_format($item['overview']['total'], 0, ',', '.')]) }}
                        </p>
                        <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                            @foreach (['approved', 'rejected', 'pending'] as $key)
                                <x-budget-outcome-stat compact :outcome="$key" :label="__('ui.budget.overview.' . $key)" :value="$item['overview'][$key]" />
                            @endforeach
                        </dl>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
