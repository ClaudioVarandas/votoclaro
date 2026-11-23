@extends('layouts.app')

<x-seo-meta
    :title="($theme ? $theme->name : __('ui.budget.matrix.title')) . ' — ' . __('ui.budget.short_name', ['year' => $budget->year]) . ' — VotoClaro'"
    :description="__('ui.budget.description')"
/>

@section('content')
    <div class="space-y-6">
        <nav aria-label="Breadcrumbs">
            <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm text-sand-500 dark:text-sand-400">
                <li><a href="{{ $legislatureContext->route('budgets.index') }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">{{ __('ui.breadcrumbs.budgets') }}</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ $legislatureContext->route('budgets.show', ['year' => $budget->year]) }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">{{ __('ui.budget.short_name', ['year' => $budget->year]) }}</a></li>
                <li aria-hidden="true">/</li>
                @if ($theme)
                    <li><a href="{{ $legislatureContext->route('budgets.proposals.index', ['year' => $budget->year]) }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">{{ __('ui.budget.matrix.title') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li>{{ $theme->name }}</li>
                @else
                    <li>{{ __('ui.budget.matrix.title') }}</li>
                @endif
            </ol>
        </nav>

        @if ($theme)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-republic-700 dark:text-republic-400">{{ __('ui.budget.matrix.theme') }}</p>
                <div class="mt-2 flex items-start gap-3 sm:items-center">
                    <span class="inline-flex shrink-0 rounded-xl bg-republic-100 p-2 text-republic-700 sm:p-2.5 dark:bg-republic-950 dark:text-republic-400">
                        <x-budget-theme-icon :theme="$theme->name" class="size-6 sm:size-7" />
                    </span>
                    <h1 class="font-serif text-3xl font-bold tracking-tight text-sand-900 sm:text-4xl dark:text-sand-100">{{ $theme->name }}</h1>
                </div>
                <p class="mt-3 text-sand-500 dark:text-sand-400">{{ trans_choice('ui.budget.matrix.theme_subtitle', $theme->proposals_count, ['count' => number_format($theme->proposals_count, 0, ',', '.'), 'budget' => __('ui.budget.name', ['year' => $budget->year])]) }}</p>
            </div>
        @else
            <div>
                <h1 class="font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">{{ __('ui.budget.matrix.title') }}</h1>
                <p class="mt-2 text-sand-500 dark:text-sand-400">{{ __('ui.budget.name', ['year' => $budget->year]) }}</p>
            </div>
        @endif

        <form method="GET" action="{{ $legislatureContext->route('budgets.proposals.index', ['year' => $budget->year]) }}"
              class="grid grid-cols-1 gap-3 rounded-2xl border border-sand-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-5 dark:border-sand-800 dark:bg-sand-900">
            @php $field = 'w-full rounded-xl border border-sand-200 bg-sand-50 px-3 py-2.5 text-sm text-sand-900 focus:border-republic-500 focus:outline-none focus:ring-1 focus:ring-republic-500 dark:border-sand-700 dark:bg-sand-950 dark:text-sand-100'; @endphp
            <div class="sm:col-span-2">
                <label for="q" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.budget.matrix.search') }}</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] }}" maxlength="100" class="{{ $field }}">
            </div>
            <div>
                <label for="tema" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.budget.matrix.theme') }}</label>
                <select id="tema" name="tema" class="{{ $field }}">
                    <option value="">{{ __('ui.budget.matrix.all_themes') }}</option>
                    @foreach ($themes as $theme)
                        <option value="{{ $theme->slug }}" @selected($filters['tema'] === $theme->slug)>{{ $theme->name }} ({{ $theme->proposals_count }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="partido" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.budget.matrix.party') }}</label>
                <select id="partido" name="partido" class="{{ $field }}">
                    <option value="">{{ __('ui.budget.matrix.all_parties') }}</option>
                    @foreach ($proposingParties as $acronym)
                        <option value="{{ strtolower($acronym) }}" @selected($filters['partido'] === $acronym)>{{ $acronym }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="estado" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.budget.matrix.status') }}</label>
                <select id="estado" name="estado" class="{{ $field }}">
                    <option value="">{{ __('ui.budget.matrix.all_statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['estado'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-5">
                <button type="submit" class="rounded-xl bg-republic-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-republic-700 dark:bg-republic-500 dark:hover:bg-republic-600">{{ __('ui.budget.matrix.filter') }}</button>
                @if (array_filter($filters))
                    <a href="{{ $legislatureContext->route('budgets.proposals.index', ['year' => $budget->year]) }}" class="text-sm text-sand-600 underline hover:text-republic-700 dark:text-sand-400 dark:hover:text-republic-400">{{ __('ui.budget.matrix.clear') }}</a>
                @endif
                <p class="ml-auto text-sm text-sand-500 dark:text-sand-400" aria-live="polite">{{ trans_choice('ui.budget.matrix.results', $proposals->total(), ['count' => number_format($proposals->total(), 0, ',', '.')]) }}</p>
            </div>
        </form>

        @if ($proposals->isEmpty())
            <p class="py-12 text-center text-sm text-sand-500 dark:text-sand-400">{{ __('ui.budget.matrix.no_results') }}</p>
        @else
            <x-budget-vote-matrix :budget="$budget" :proposals="$proposals" :parties="$parties" :cells="$cells" :totals="$totals" />

            <p class="text-xs text-sand-500 dark:text-sand-400">{{ __('ui.budget.matrix.legend') }}</p>

            {{ $proposals->links() }}
        @endif
    </div>
@endsection
