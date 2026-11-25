@extends('layouts.app')

<x-seo-meta
    :title="__('ui.budget.articles.title') . ' — ' . __('ui.budget.short_name', ['year' => $budget->year]) . ' — VotoClaro'"
    :description="__('ui.budget.articles.intro')"
/>

@section('content')
    <div class="space-y-6">
        <nav aria-label="Breadcrumbs">
            <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm text-sand-500 dark:text-sand-400">
                <li><a href="{{ $legislatureContext->route('budgets.index') }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">{{ __('ui.breadcrumbs.budgets') }}</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ $legislatureContext->route('budgets.show', ['year' => $budget->year]) }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">{{ __('ui.budget.short_name', ['year' => $budget->year]) }}</a></li>
                <li aria-hidden="true">/</li>
                <li>{{ __('ui.budget.articles.title') }}</li>
            </ol>
        </nav>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-sky-700 dark:text-sky-400">{{ __('ui.budget.name', ['year' => $budget->year]) }}</p>
            <h1 class="mt-1 font-serif text-3xl font-bold tracking-tight text-sand-900 sm:text-4xl dark:text-sand-100">{{ __('ui.budget.articles.title') }}</h1>
            <p class="mt-2 max-w-3xl text-sand-500 dark:text-sand-400">{{ __('ui.budget.articles.intro') }}</p>
        </div>

        <form method="GET" action="{{ $legislatureContext->route('budgets.articles.index', ['year' => $budget->year]) }}"
              class="grid grid-cols-1 gap-3 rounded-2xl border border-sand-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-sand-800 dark:bg-sand-900">
            @php $field = 'w-full rounded-xl border border-sand-200 bg-sand-50 px-3 py-2.5 text-sm text-sand-900 focus:border-republic-500 focus:outline-none focus:ring-1 focus:ring-republic-500 dark:border-sand-700 dark:bg-sand-950 dark:text-sand-100'; @endphp
            <div class="sm:col-span-2">
                <label for="q" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.budget.articles.search') }}</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] }}" maxlength="100" class="{{ $field }}">
            </div>
            <div>
                <label for="estado" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.budget.matrix.status') }}</label>
                <select id="estado" name="estado" class="{{ $field }}">
                    <option value="">{{ __('ui.budget.matrix.all_statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['estado'] === $status->value)>{{ $status->articleLabel($closed) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 py-2.5 text-sm text-sand-700 dark:text-sand-300">
                    <input type="checkbox" name="lei" value="1" @checked($filters['lei']) class="size-4 rounded border-sand-300 text-republic-600 focus:ring-republic-500 dark:border-sand-700 dark:bg-sand-950">
                    {{ __('ui.budget.articles.amends_law_filter') }}
                </label>
            </div>
            <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-4">
                <button type="submit" class="rounded-xl bg-republic-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-republic-700 dark:bg-republic-500 dark:hover:bg-republic-600">{{ __('ui.budget.matrix.filter') }}</button>
                @if (array_filter($filters))
                    <a href="{{ $legislatureContext->route('budgets.articles.index', ['year' => $budget->year]) }}" class="text-sm text-sand-600 underline hover:text-republic-700 dark:text-sand-400 dark:hover:text-republic-400">{{ __('ui.budget.matrix.clear') }}</a>
                @endif
                <p class="ml-auto text-sm text-sand-500 dark:text-sand-400" aria-live="polite">{{ trans_choice('ui.budget.articles.results', $articles->total(), ['count' => number_format($articles->total(), 0, ',', '.')]) }}</p>
            </div>
        </form>

        @if ($articles->isEmpty())
            <p class="py-12 text-center text-sm text-sand-500 dark:text-sand-400">{{ __('ui.budget.articles.no_results') }}</p>
        @else
            @if ($cells === [])
                <p class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">{{ __('ui.budget.articles.not_voted_yet') }}</p>
            @endif

            <x-budget-vote-matrix :budget="$budget" :proposals="$articles" :parties="$parties" :cells="$cells" :totals="$totals" :closed="$closed" for-articles />

            <p class="text-xs text-sand-500 dark:text-sand-400">{{ __('ui.budget.articles.legend') }}</p>

            {{ $articles->links() }}
        @endif
    </div>
@endsection
