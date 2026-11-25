@extends('layouts.app')

<x-seo-meta
    :title="__('ui.budget.proposal.number', ['number' => $proposal->number]) . ': ' . $proposal->title . ' — VotoClaro'"
    :description="__('ui.budget.name', ['year' => $budget->year])"
/>

@section('content')
    <div class="space-y-8">
        <nav aria-label="Breadcrumbs">
            <ol class="flex flex-wrap items-center gap-1.5 text-sm text-sand-500 dark:text-sand-400">
                <li><a href="{{ $legislatureContext->route('budgets.index') }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">{{ __('ui.breadcrumbs.budgets') }}</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ $legislatureContext->route('budgets.show', ['year' => $budget->year]) }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">{{ __('ui.budget.short_name', ['year' => $budget->year]) }}</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ $legislatureContext->route('budgets.proposals.index', ['year' => $budget->year]) }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">{{ __('ui.budget.matrix.title') }}</a></li>
                <li aria-hidden="true">/</li>
                <li class="font-mono text-xs">{{ $proposal->number }}</li>
            </ol>
        </nav>

        <div class="space-y-3">
            <p class="text-sm font-semibold text-sand-500 dark:text-sand-400">
                {{ __('ui.budget.proposal.number', ['number' => $proposal->number]) }}
                @if ($proposal->location())
                    <span class="font-normal">· {{ $proposal->location() }}</span>
                @endif
            </p>
            <h1 class="font-serif text-2xl font-bold leading-tight tracking-tight text-sand-900 sm:text-3xl dark:text-sand-100">{{ $proposal->subject() }}</h1>
            <x-budget-status-badge :status="$proposal->status" :closed="$closed" class="text-sm" />
        </div>

        @if ($proposal->articles->isNotEmpty())
            <p class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200">
                {{ __('ui.budget.proposal.amends_article') }}
                @foreach ($proposal->articles as $article)
                    <a href="{{ $legislatureContext->route('budgets.articles.show', ['year' => $budget->year, 'article' => $article->slug]) }}" class="font-semibold underline hover:text-sky-700 dark:hover:text-sky-300">{{ $article->number }}{{ $article->title ? ' — ' . $article->title : '' }}</a>@if (! $loop->last), @endif
                @endforeach
            </p>
        @endif

        <dl class="grid grid-cols-1 gap-px overflow-hidden rounded-2xl border border-sand-200 bg-sand-200 sm:grid-cols-2 dark:border-sand-800 dark:bg-sand-800">
            @php
                $details = [
                    'proposed_by' => $proposal->parties->isNotEmpty() ? $proposal->parties->pluck('acronym')->implode(', ') : $proposal->proposer_label,
                    'themes' => $proposal->themes->pluck('name')->implode(' · '),
                    'type' => $proposal->type,
                    'submitted' => $proposal->submitted_at?->format('d/m/Y'),
                ];
            @endphp
            @foreach ($details as $key => $value)
                <div class="bg-white p-4 dark:bg-sand-900">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.budget.proposal.' . $key) }}</dt>
                    <dd class="mt-1 text-sm font-medium text-sand-900 dark:text-sand-100">{{ $value ?: '—' }}</dd>
                </div>
            @endforeach
        </dl>

        @if ($proposal->pdf_url)
            <a href="{{ $proposal->pdf_url }}" rel="noopener" target="_blank" class="inline-flex items-center gap-2 text-sm font-medium text-republic-600 underline hover:text-republic-700 dark:text-republic-400 dark:hover:text-republic-300">{{ __('ui.budget.proposal.pdf') }}</a>
        @endif

        <section class="space-y-4" aria-labelledby="votes-title">
            <h2 id="votes-title" class="font-serif text-xl font-bold text-sand-900 dark:text-sand-100">{{ __('ui.budget.proposal.votes') }}</h2>

            <x-budget-vote-list :votes="$proposal->votes" :parties="$parties" :empty="__($closed ? 'ui.budget.proposal.no_votes_closed' : 'ui.budget.proposal.no_votes')" />
        </section>

        @if ($proposal->text)
            <section class="space-y-3" aria-labelledby="text-title">
                <h2 id="text-title" class="font-serif text-xl font-bold text-sand-900 dark:text-sand-100">{{ __('ui.budget.proposal.text') }}</h2>
                <div class="whitespace-pre-line rounded-2xl border border-sand-200 bg-white p-5 text-sm leading-relaxed text-sand-700 dark:border-sand-800 dark:bg-sand-900 dark:text-sand-300">{{ $proposal->text }}</div>
            </section>
        @endif

        <a href="{{ $legislatureContext->route('budgets.proposals.index', ['year' => $budget->year]) }}" class="inline-block text-sm font-medium text-republic-600 underline hover:text-republic-700 dark:text-republic-400 dark:hover:text-republic-300">{{ __('ui.budget.proposal.back') }}</a>
    </div>
@endsection
