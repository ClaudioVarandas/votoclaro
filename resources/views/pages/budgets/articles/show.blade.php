@extends('layouts.app')

<x-seo-meta
    :title="$article->number . ($article->title ? ': ' . $article->title : '') . ' — ' . __('ui.budget.short_name', ['year' => $budget->year]) . ' — VotoClaro'"
    :description="__('ui.budget.articles.intro')"
/>

@section('content')
    <div class="space-y-8">
        <nav aria-label="Breadcrumbs">
            <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm text-sand-500 dark:text-sand-400">
                <li><a href="{{ $legislatureContext->route('budgets.index') }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">{{ __('ui.breadcrumbs.budgets') }}</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ $legislatureContext->route('budgets.show', ['year' => $budget->year]) }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">{{ __('ui.budget.short_name', ['year' => $budget->year]) }}</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ $legislatureContext->route('budgets.articles.index', ['year' => $budget->year]) }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">{{ __('ui.budget.articles.title') }}</a></li>
                <li aria-hidden="true">/</li>
                <li>{{ $article->number }}</li>
            </ol>
        </nav>

        <div class="space-y-3">
            <p class="text-sm font-semibold text-sky-700 dark:text-sky-400">{{ __('ui.budget.articles.eyebrow', ['number' => $article->number]) }}</p>
            <h1 class="font-serif text-2xl font-bold leading-tight tracking-tight text-sand-900 sm:text-3xl dark:text-sand-100">{{ $article->title ?? $article->number }}</h1>
            <x-budget-status-badge :status="$article->status" article class="text-sm" />
        </div>

        @if ($article->amendsLaw())
            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950">
                <p class="text-xs font-semibold uppercase tracking-wider text-sky-700 dark:text-sky-400">{{ __('ui.budget.articles.amends_law') }}</p>
                <p class="mt-1 font-medium text-sky-950 dark:text-sky-100">{{ $article->law_subtitle ?? $article->law_title }}</p>
                @if ($article->law_subtitle)
                    <p class="text-sm text-sky-800 dark:text-sky-300">{{ $article->law_title }}</p>
                @endif
            </div>
        @endif

        <section class="space-y-4" aria-labelledby="votes-title">
            <h2 id="votes-title" class="font-serif text-xl font-bold text-sand-900 dark:text-sand-100">{{ __('ui.budget.proposal.votes') }}</h2>
            <x-budget-vote-list :votes="$article->votes" :parties="$parties" :empty="__('ui.budget.articles.no_votes')" article />
        </section>

        <section class="space-y-4" aria-labelledby="amendments-title">
            <div>
                <h2 id="amendments-title" class="font-serif text-xl font-bold text-sand-900 dark:text-sand-100">{{ __('ui.budget.articles.amendments_title') }}</h2>
                <p class="mt-1 text-sm text-sand-500 dark:text-sand-400">{{ trans_choice('ui.budget.articles.amendments_count', $article->proposals->count(), ['count' => $article->proposals->count()]) }}</p>
            </div>
            @if ($article->proposals->isNotEmpty())
                <ul class="divide-y divide-sand-100 overflow-hidden rounded-2xl border border-sand-200 bg-white dark:divide-sand-800 dark:border-sand-800 dark:bg-sand-900">
                    @foreach ($article->proposals as $proposal)
                        <li>
                            <a href="{{ $legislatureContext->route('budgets.proposals.show', ['year' => $budget->year, 'proposal' => $proposal->slug]) }}" class="group flex flex-wrap items-center gap-x-3 gap-y-1 px-4 py-3 transition-colors hover:bg-sand-50 dark:hover:bg-sand-950">
                                <span class="w-24 shrink-0 text-xs font-semibold text-sand-500 dark:text-sand-400">{{ $proposal->parties->pluck('acronym')->implode(', ') ?: $proposal->proposer_label }} · <span class="font-mono">{{ $proposal->number }}</span></span>
                                <span class="min-w-0 flex-1 text-sm text-sand-900 group-hover:text-republic-700 group-hover:underline dark:text-sand-100 dark:group-hover:text-republic-400">{{ $proposal->subject() }}</span>
                                <x-budget-status-badge :status="$proposal->status" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        @if ($article->text)
            <section class="space-y-3" aria-labelledby="text-title">
                <h2 id="text-title" class="font-serif text-xl font-bold text-sand-900 dark:text-sand-100">{{ __('ui.budget.articles.text') }}</h2>
                <div class="whitespace-pre-line rounded-2xl border border-sand-200 bg-white p-5 text-sm leading-relaxed text-sand-700 dark:border-sand-800 dark:bg-sand-900 dark:text-sand-300">{{ $article->text }}</div>
            </section>
        @endif

        <a href="{{ $legislatureContext->route('budgets.articles.index', ['year' => $budget->year]) }}" class="inline-block text-sm font-medium text-republic-600 underline hover:text-republic-700 dark:text-republic-400 dark:hover:text-republic-300">{{ __('ui.budget.articles.back') }}</a>
    </div>
@endsection
