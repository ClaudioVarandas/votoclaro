@props(['votes', 'parties', 'empty', 'article' => false])

<div {{ $attributes->class(['space-y-4']) }}>
    @php $marksFinal = $votes->contains(fn ($vote) => ! $vote->is_final); @endphp
    @forelse ($votes->sortByDesc('is_final') as $vote)
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
                    <x-budget-status-badge :status="$vote->result" :article="$article" />
                @endif
                @if ($marksFinal && $vote->is_final)
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
        <p class="rounded-2xl border border-sand-200 bg-white p-5 text-sm text-sand-500 dark:border-sand-800 dark:bg-sand-900 dark:text-sand-400">{{ $empty }}</p>
    @endforelse
</div>
