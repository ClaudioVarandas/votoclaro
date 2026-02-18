@extends('layouts.app')

<x-seo-meta
    :title="$initiative->title . ' — VotoClaro'"
    :description="__('ui.initiatives.description')"
/>

@section('content')
    <div class="space-y-8">
        {{-- Breadcrumbs --}}
        <nav aria-label="Breadcrumbs">
            <ol class="flex items-center gap-1.5 text-sm text-sand-500 dark:text-sand-400">
                <li>
                    <a href="{{ route('dashboard') }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">
                        {{ __('ui.breadcrumbs.dashboard') }}
                    </a>
                </li>
                <li>
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </li>
                <li>
                    <a href="{{ route('initiatives.index') }}" class="transition-colors hover:text-republic-700 dark:hover:text-republic-400">
                        {{ __('ui.breadcrumbs.initiatives') }}
                    </a>
                </li>
                <li>
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </li>
                <li class="font-mono text-xs">{{ $initiative->id }}</li>
            </ol>
        </nav>

        {{-- Title area --}}
        <div class="space-y-3">
            @if ($initiative->author_category === 'government')
                <x-government-badge />
            @endif

            <h1 class="font-serif text-2xl font-bold leading-tight tracking-tight text-sand-900 sm:text-3xl dark:text-sand-100">
                {{ $initiative->title }}
            </h1>
        </div>

        {{-- Metadata summary card --}}
        <div class="rounded-2xl border border-sand-200 bg-white dark:border-sand-800 dark:bg-sand-900">
            <div class="grid grid-cols-1 divide-y divide-sand-200 sm:grid-cols-2 sm:divide-y-0 dark:divide-sand-800">
                {{-- Estado --}}
                <div class="order-1 border-sand-200 p-4 sm:border-b sm:border-r dark:border-sand-800">
                    <div class="flex items-center gap-2 text-sand-500 dark:text-sand-400">
                        @if ($initiative->status === 'approved')
                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif ($initiative->status === 'rejected')
                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @else
                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                        <span class="text-xs font-semibold uppercase tracking-wider">{{ __('ui.initiatives.state') }}</span>
                    </div>
                    <div class="mt-2">
                        <x-status-badge :status="$initiative->status" size="md" />
                    </div>
                </div>

                {{-- Tipo --}}
                @php
                    $typeKey = $initiative->initiative_type_key;
                    $hasExplanation = $typeKey && __('ui.initiative_type_info.' . $typeKey) !== 'ui.initiative_type_info.' . $typeKey;
                @endphp
                <div class="order-2 border-sand-200 p-4 sm:border-b dark:border-sand-800" x-data="{ open: false }">
                    <div class="flex items-center gap-2 text-sand-500 dark:text-sand-400">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                        </svg>
                        <span class="text-xs font-semibold uppercase tracking-wider">{{ __('ui.initiatives.type') }}</span>
                    </div>
                    <div class="mt-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-sand-900 dark:text-sand-100">{{ $initiative->initiative_type_label }}</span>
                            @if ($hasExplanation)
                                <button
                                    type="button"
                                    @click="open = !open"
                                    :aria-expanded="open.toString()"
                                    class="text-sand-400 transition-colors hover:text-sand-600 dark:text-sand-500 dark:hover:text-sand-300"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                                    </svg>
                                    <span class="sr-only">{{ __('ui.initiatives.what_does_it_mean') }}</span>
                                </button>
                            @endif
                        </div>
                        @if ($hasExplanation)
                            <p x-show="open" x-cloak x-transition class="mt-2 text-sm text-sand-600 dark:text-sand-400">
                                {{ __('ui.initiative_type_info.' . $typeKey) }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Autor --}}
                <div class="order-3 border-sand-200 p-4 sm:border-b sm:border-r dark:border-sand-800">
                    <div class="flex items-center gap-2 text-sand-500 dark:text-sand-400">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                        <span class="text-xs font-semibold uppercase tracking-wider">{{ __('ui.initiatives.author') }}</span>
                    </div>
                    <div class="mt-2">
                        @if ($initiative->author_category === 'parliamentary_group' && $initiative->author_party)
                            <a href="{{ route('parties.show', strtolower($initiative->author_party)) }}" class="text-sm font-medium text-republic-600 underline transition-colors hover:text-republic-700 dark:text-republic-400 dark:hover:text-republic-300">
                                {{ $initiative->author_label }}
                            </a>
                        @else
                            <span class="text-sm font-medium text-sand-900 dark:text-sand-100">{{ $initiative->author_label }}</span>
                        @endif
                    </div>
                </div>

                {{-- Duração --}}
                <div class="order-4 border-sand-200 p-4 sm:border-b dark:border-sand-800">
                    <div class="flex items-center gap-2 text-sand-500 dark:text-sand-400">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-xs font-semibold uppercase tracking-wider">{{ __('ui.initiatives.duration') }}</span>
                    </div>
                    <div class="mt-2 text-sm font-medium text-sand-900 dark:text-sand-100">
                        @if ($initiative->status === 'approved' && $initiative->days_to_approval !== null)
                            {{ __('ui.initiatives.approved_in_days', ['count' => $initiative->days_to_approval]) }}
                        @elseif ($initiative->status === 'rejected' && $initiative->days_to_rejection !== null)
                            {{ __('ui.initiatives.rejected_in_days', ['count' => $initiative->days_to_rejection]) }}
                        @elseif ($initiative->status === 'in_progress' && $initiative->days_in_progress !== null)
                            {{ __('ui.initiatives.days_in_progress', ['count' => $initiative->days_in_progress]) }}
                        @else
                            {{ __('ui.initiatives.duration_pending') }}
                        @endif
                    </div>
                </div>

                {{-- Entrada --}}
                <div class="order-5 border-sand-200 p-4 sm:border-r dark:border-sand-800">
                    <div class="flex items-center gap-2 text-sand-500 dark:text-sand-400">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                        <span class="text-xs font-semibold uppercase tracking-wider">{{ __('ui.initiatives.entry') }}</span>
                    </div>
                    <div class="mt-2 text-sm font-medium text-sand-900 dark:text-sand-100">
                        {{ $initiative->entry_date ? $initiative->entry_date->format('d/m/Y') : __('ui.initiatives.pending') }}
                    </div>
                </div>

                {{-- Votação Final --}}
                <div class="order-6 p-4">
                    <div class="flex items-center gap-2 text-sand-500 dark:text-sand-400">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                        </svg>
                        <span class="text-xs font-semibold uppercase tracking-wider">{{ __('ui.initiatives.final_vote') }}</span>
                    </div>
                    <div class="mt-2 text-sm font-medium text-sand-900 dark:text-sand-100">
                        {{ $initiative->final_vote_date ? $initiative->final_vote_date->format('d/m/Y') : __('ui.initiatives.pending') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Votes section --}}
        <div class="space-y-6">
            <h2 class="font-serif text-xl font-semibold text-sand-900 dark:text-sand-100">
                {{ __('ui.initiatives.votes_section') }}
            </h2>

            @if ($votesWithPositions->isEmpty())
                <div class="rounded-2xl border border-sand-200 bg-white px-6 py-8 text-center dark:border-sand-800 dark:bg-sand-900">
                    <p class="text-sm text-sand-500 dark:text-sand-400">
                        {{ __('ui.initiatives.no_votes') }}
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($votesWithPositions as $voteData)
                        <div class="rounded-2xl border border-sand-200 bg-white p-6 dark:border-sand-800 dark:bg-sand-900">
                            {{-- Vote header --}}
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-semibold text-sand-900 dark:text-sand-100">
                                        {{ __('ui.initiatives.vote_on') }} {{ $voteData['vote']->date?->format('d/m/Y') }}
                                    </h3>
                                    @if ($voteData['vote']->unanimous)
                                        <span class="rounded-full bg-republic-100 px-2 py-0.5 text-xs font-medium text-republic-800 dark:bg-republic-950 dark:text-republic-300">
                                            {{ __('ui.initiatives.unanimous') }}
                                        </span>
                                    @endif
                                </div>
                                @if ($voteData['vote']->result)
                                    <span class="text-sm font-medium text-sand-600 dark:text-sand-400">
                                        {{ __('ui.vote_result.' . $voteData['vote']->result) }}
                                    </span>
                                @endif
                            </div>

                            {{-- Summary bar --}}
                            <div class="mt-4">
                                <x-vote-summary-bar :counts="$voteData['counts']" />
                            </div>

                            {{-- Party breakdown --}}
                            <div class="mt-4 space-y-3">
                                @foreach (['favor', 'contra', 'abstencao'] as $positionType)
                                    @if (count($voteData['positions'][$positionType]) > 0)
                                        <div>
                                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">
                                                {{ __('ui.position.' . $positionType) }}
                                            </p>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($voteData['positions'][$positionType] as $party)
                                                    <x-party-position-badge :party="$party" :position="$positionType" />
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
