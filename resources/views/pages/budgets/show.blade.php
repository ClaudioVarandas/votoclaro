@extends('layouts.app')

<x-seo-meta
    :title="__('ui.budget.name', ['year' => $budget->year]) . ' — VotoClaro'"
    :description="__('ui.budget.description')"
/>

@section('content')
    <div class="space-y-8">
        <nav aria-label="Breadcrumbs">
            <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm text-sand-500 dark:text-sand-400">
                <li><a href="{{ $legislatureContext->route('budgets.index') }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">{{ __('ui.breadcrumbs.budgets') }}</a></li>
                <li aria-hidden="true">/</li>
                <li>{{ __('ui.budget.short_name', ['year' => $budget->year]) }}</li>
            </ol>
        </nav>

        <div class="space-y-3">
            <h1 class="font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">{{ __('ui.budget.name', ['year' => $budget->year]) }}</h1>
            <p class="max-w-3xl text-sand-600 dark:text-sand-400">{{ __('ui.budget.what_is') }}</p>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                @if ($budget->initiative)
                    <a href="{{ route('initiatives.show', $budget->initiative) }}" class="font-medium text-republic-600 underline hover:text-republic-700 dark:text-republic-400 dark:hover:text-republic-300">{{ __('ui.budget.see_bill') }}</a>
                @endif
                <a href="{{ $legislatureContext->route('budgets.proposals.index', ['year' => $budget->year]) }}" class="font-medium text-republic-600 underline hover:text-republic-700 dark:text-republic-400 dark:hover:text-republic-300">{{ __('ui.budget.see_proposals') }}</a>
            </div>
        </div>

        {{-- Overview --}}
        <section class="space-y-3">
            <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach (['total' => 'proposals', 'approved' => 'approved', 'rejected' => 'rejected', 'pending' => 'pending'] as $key => $outcome)
                    <x-budget-outcome-stat :outcome="$outcome" :label="__('ui.budget.overview.' . $outcome)" :value="$overview[$key]" />
                @endforeach
            </dl>
            @if ($overview['pending'] > 0)
                <p class="text-sm text-sand-500 dark:text-sand-400">{{ __('ui.budget.voting_progress', ['voted' => number_format($overview['voted'], 0, ',', '.'), 'total' => number_format($overview['total'], 0, ',', '.')]) }}</p>
            @endif
        </section>

        {{-- Parties --}}
        <section class="space-y-3" aria-labelledby="parties-title">
            <div>
                <h2 id="parties-title" class="font-serif text-xl font-bold text-sand-900 dark:text-sand-100">{{ __('ui.budget.parties_title') }}</h2>
                <p class="mt-1 text-sm text-sand-500 dark:text-sand-400">{{ __('ui.budget.parties_intro') }}</p>
            </div>
            <div class="overflow-x-auto rounded-2xl border border-sand-200 bg-white dark:border-sand-800 dark:bg-sand-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-sand-200 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 dark:border-sand-800 dark:text-sand-400">
                            <th scope="col" class="px-4 py-3">{{ __('ui.budget.party') }}</th>
                            <th scope="col" class="px-4 py-3 text-right">{{ __('ui.budget.proposed') }}</th>
                            <th scope="col" class="px-4 py-3 text-right">{{ __('ui.budget.approved') }}</th>
                            <th scope="col" class="px-4 py-3 text-right">{{ __('ui.budget.approval_rate') }}</th>
                            <th scope="col" class="px-4 py-3 text-right">{{ __('ui.budget.against_others') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand-100 dark:divide-sand-800">
                        @foreach ($parties as $party)
                            <tr>
                                <th scope="row" class="px-4 py-3 text-left font-semibold">
                                    @if ($party['proposed'] > 0)
                                        <a href="{{ $legislatureContext->route('budgets.proposals.index', ['year' => $budget->year, 'partido' => strtolower($party['acronym'])]) }}" class="text-republic-700 hover:underline dark:text-republic-400">{{ $party['acronym'] }}</a>
                                    @else
                                        <span class="text-sand-900 dark:text-sand-100">{{ $party['acronym'] }}</span>
                                    @endif
                                </th>
                                <td class="px-4 py-3 text-right tabular-nums text-sand-700 dark:text-sand-300">{{ number_format($party['proposed'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-sand-700 dark:text-sand-300">{{ number_format($party['approved'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-sand-700 dark:text-sand-300">{{ $party['proposed'] > 0 ? number_format($party['approval_rate'], 1, ',', '.') . '%' : '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-sand-700 dark:text-sand-300">
                                    {{ number_format($party['against_others'], 0, ',', '.') }}
                                    <span class="text-sand-400 dark:text-sand-500">/ {{ number_format($party['others_voted'], 0, ',', '.') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Themes --}}
        <section class="space-y-3" aria-labelledby="themes-title">
            <div>
                <h2 id="themes-title" class="font-serif text-xl font-bold text-sand-900 dark:text-sand-100">{{ __('ui.budget.themes_title') }}</h2>
                <p class="mt-1 text-sm text-sand-500 dark:text-sand-400">{{ __('ui.budget.themes_intro') }}</p>
            </div>
            <ul class="flex flex-wrap gap-2">
                @foreach ($themes as $theme)
                    <li>
                        <a href="{{ $legislatureContext->route('budgets.proposals.index', ['year' => $budget->year, 'tema' => $theme->slug]) }}"
                           class="group inline-flex items-center gap-2 rounded-xl border border-sand-200 bg-white px-3 py-2 text-sm text-sand-700 transition-colors hover:border-republic-300 hover:text-republic-700 dark:border-sand-800 dark:bg-sand-900 dark:text-sand-300 dark:hover:border-republic-700 dark:hover:text-republic-400">
                            <x-budget-theme-icon :theme="$theme->name" class="size-4 text-republic-600 dark:text-republic-400" />
                            {{ $theme->name }}
                            <span class="rounded-full bg-sand-100 px-1.5 text-xs tabular-nums text-sand-600 dark:bg-sand-800 dark:text-sand-400">{{ $theme->proposals_count }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>

        @if ($budget->last_imported_at)
            <p class="text-xs text-sand-500 dark:text-sand-400">{{ __('ui.budget.last_imported', ['date' => $budget->last_imported_at->format('d/m/Y')]) }}</p>
        @endif
    </div>
@endsection
