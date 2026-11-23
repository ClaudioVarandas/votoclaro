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
            <x-budget-status-badge :status="$proposal->status" class="text-sm" />
        </div>

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

            @forelse ($proposal->votes->sortByDesc('is_final') as $vote)
                @php
                    $byParty = $vote->positions->whereNull('deputy')->keyBy('party');
                    $deputies = $vote->positions->whereNotNull('deputy');
                @endphp
                <article class="space-y-4 rounded-2xl border border-sand-200 bg-white p-5 dark:border-sand-800 dark:bg-sand-900">
                    <header class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-sand-900 dark:text-sand-100">{{ $vote->stage->label() }}</span>
                        @if ($vote->date)
                            <span class="text-sm text-sand-500 dark:text-sand-400">· {{ $vote->date->format('d/m/Y') }}</span>
                        @endif
                        @if ($vote->result)
                            <x-budget-status-badge :status="$vote->result" />
                        @endif
                        @if ($vote->is_final)
                            <span class="rounded-full bg-sand-900 px-2 py-0.5 text-xs font-medium text-white dark:bg-sand-100 dark:text-sand-900">{{ __('ui.budget.proposal.vote_final') }}</span>
                        @endif
                    </header>
                    @if ($vote->subject)
                        <p class="text-sm text-sand-600 dark:text-sand-400">{{ $vote->subject }}</p>
                    @endif
                    <ul class="flex flex-wrap gap-2">
                        @foreach (collect($parties)->merge($byParty->keys())->unique() as $party)
                            @continue(! $byParty->has($party))
                            @php $position = $byParty[$party]->position; @endphp
                            <li>
                                <x-party-position-badge :party="$party . ' ' . $position->symbol()" :position="$position->value" title="{{ $party }}: {{ $position->label() }}" />
                            </li>
                        @endforeach
                    </ul>
                    @if ($deputies->isNotEmpty())
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.budget.proposal.deputies') }}</p>
                            <ul class="mt-2 flex flex-wrap gap-2">
                                @foreach ($deputies as $deputy)
                                    <li><x-party-position-badge :party="$deputy->deputy . ($deputy->party ? ' (' . $deputy->party . ')' : '') . ' ' . $deputy->position->symbol()" :position="$deputy->position->value" /></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </article>
            @empty
                <p class="rounded-2xl border border-sand-200 bg-white p-5 text-sm text-sand-500 dark:border-sand-800 dark:bg-sand-900 dark:text-sand-400">{{ __('ui.budget.proposal.no_votes') }}</p>
            @endforelse
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
