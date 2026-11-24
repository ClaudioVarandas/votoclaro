<x-section-panel id="comparison-title" :title="__('ui.budget.comparison.title', ['year' => $previousYear()])" :intro="__('ui.budget.comparison.intro', ['year' => $previousYear()])" icon="chart-bar" accent="blue">
    <div class="space-y-8">
        @if ($legislatureNote())
            <p class="rounded-xl bg-sand-50 px-4 py-3 text-sm text-sand-600 dark:bg-sand-950 dark:text-sand-400">{{ $legislatureNote() }}</p>
        @endif

        {{-- Totals --}}
        <div class="-mx-5 overflow-x-auto sm:-mx-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-y border-sand-200 bg-sand-50 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 dark:border-sand-800 dark:bg-sand-950 dark:text-sand-400">
                        <th scope="col" class="py-3 pr-2 pl-5 sm:px-6"><span class="sr-only">{{ __('ui.budget.comparison.figure') }}</span></th>
                        <th scope="col" class="px-2 py-3 text-right sm:px-4">{{ $budget->year }}</th>
                        <th scope="col" class="px-2 py-3 text-right sm:px-4">{{ $previousYear() }}</th>
                        <th scope="col" class="py-3 pr-5 pl-2 text-right sm:px-6">{{ __('ui.budget.comparison.difference') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand-100 dark:divide-sand-800">
                    @foreach ($totalRows() as $row)
                        <tr>
                            <th scope="row" class="py-3 pr-2 pl-5 text-left font-medium text-sand-800 sm:px-6 dark:text-sand-200">{{ $row['label'] }}</th>
                            <td class="px-2 py-3 text-right font-serif text-base font-bold tabular-nums text-sand-900 sm:px-4 dark:text-sand-100">{{ $row['current'] }}</td>
                            <td class="px-2 py-3 text-right tabular-nums text-sand-600 sm:px-4 dark:text-sand-400">{{ $row['previous'] }}</td>
                            <td class="py-3 pr-5 pl-2 text-right text-xs font-semibold whitespace-nowrap tabular-nums text-sand-700 sm:px-6 sm:text-sm dark:text-sand-300">
                                <span aria-hidden="true" @class(['text-sky-600 dark:text-sky-400' => $row['direction'] === 'up', 'text-sand-400 dark:text-sand-500' => $row['direction'] !== 'up'])>{{ $row['arrow'] }}</span>
                                {{ $row['delta'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pace of the vote --}}
        @if ($hasPace())
            <div class="space-y-3">
                <div>
                    <h3 class="text-sm font-semibold text-sand-900 dark:text-sand-100">{{ __('ui.budget.comparison.pace_title') }}</h3>
                    @if ($paceSummary())
                        <p class="mt-1 text-sm text-sand-600 dark:text-sand-400">{{ $paceSummary() }}</p>
                    @endif
                </div>
                <ol class="space-y-3">
                    @foreach ($paceRows() as $day)
                        <li class="flex items-center gap-3">
                            <span class="w-14 shrink-0 text-xs font-semibold text-sand-500 dark:text-sand-400">{{ __('ui.budget.comparison.day', ['day' => $day['day']]) }}</span>
                            <span class="min-w-0 flex-1 space-y-1">
                                <span class="flex items-center gap-2">
                                    <x-progress-bar :share="$day['current_share']" color="blue" thick class="flex-1" />
                                    <span class="w-24 shrink-0 text-xs tabular-nums text-sand-800 dark:text-sand-200"><span class="font-semibold">{{ $day['current'] ?? '—' }}</span> {{ $budget->year }}</span>
                                </span>
                                <span class="flex items-center gap-2">
                                    <x-progress-bar :share="$day['previous_share']" color="sand" thick class="flex-1" />
                                    <span class="w-24 shrink-0 text-xs tabular-nums text-sand-500 dark:text-sand-400"><span class="font-semibold">{{ $day['previous'] ?? '—' }}</span> {{ $previousYear() }}</span>
                                </span>
                            </span>
                        </li>
                    @endforeach
                </ol>
                <p class="text-xs text-sand-500 dark:text-sand-400">{{ __('ui.budget.comparison.pace_help') }}</p>
            </div>
        @endif

        {{-- Parties --}}
        <div class="space-y-3">
            <h3 class="text-sm font-semibold text-sand-900 dark:text-sand-100">{{ __('ui.budget.comparison.parties_title') }}</h3>
            <div class="-mx-5 overflow-x-auto sm:-mx-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-y border-sand-200 bg-sand-50 text-xs font-semibold uppercase tracking-wider text-sand-500 dark:border-sand-800 dark:bg-sand-950 dark:text-sand-400">
                            <th scope="col" rowspan="2" class="py-2 pr-2 pl-5 text-left sm:px-6">{{ __('ui.budget.party') }}</th>
                            <th scope="colgroup" colspan="2" class="px-2 pt-2 text-center sm:px-4">{{ __('ui.budget.proposed') }}</th>
                            <th scope="colgroup" colspan="2" class="py-2 pr-5 pl-2 pt-2 text-center sm:px-6">{{ __('ui.budget.comparison.approval_rate') }}</th>
                        </tr>
                        <tr class="border-b border-sand-200 bg-sand-50 text-xs font-semibold text-sand-500 dark:border-sand-800 dark:bg-sand-950 dark:text-sand-400">
                            <th scope="col" class="px-2 pb-2 text-right sm:px-4">{{ $budget->year }}</th>
                            <th scope="col" class="px-2 pb-2 text-right sm:px-4">{{ $previousYear() }}</th>
                            <th scope="col" class="px-2 pb-2 text-right sm:px-4">{{ $budget->year }}</th>
                            <th scope="col" class="pb-2 pr-5 pl-2 text-right sm:px-6">{{ $previousYear() }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand-100 dark:divide-sand-800">
                        @foreach ($partyRows() as $party)
                            <tr>
                                <th scope="row" class="py-2.5 pr-2 pl-5 text-left sm:px-6">
                                    <span class="inline-flex justify-center whitespace-nowrap rounded-lg bg-sand-100 px-2.5 py-1 text-sm font-bold text-sand-900 sm:min-w-14 dark:bg-sand-800 dark:text-sand-100">{{ $party['acronym'] }}</span>
                                </th>
                                <td class="px-2 py-2.5 text-right font-semibold tabular-nums text-sand-900 sm:px-4 dark:text-sand-100">{{ $party['proposed'] }}</td>
                                <td class="px-2 py-2.5 text-right tabular-nums text-sand-500 sm:px-4 dark:text-sand-400">{{ $party['proposed_before'] }}</td>
                                <td class="px-2 py-2.5 text-right font-semibold whitespace-nowrap tabular-nums text-sand-900 sm:px-4 dark:text-sand-100">
                                    @if ($party['rate_direction'] !== 'same')
                                        <span aria-hidden="true" @class(['text-xs', 'text-sky-600 dark:text-sky-400' => $party['rate_direction'] === 'up', 'text-sand-400 dark:text-sand-500' => $party['rate_direction'] === 'down'])>{{ $arrow($party['rate_direction']) }}</span>
                                    @endif
                                    {{ $party['rate'] }}
                                </td>
                                <td class="py-2.5 pr-5 pl-2 text-right tabular-nums text-sand-500 sm:px-6 dark:text-sand-400">{{ $party['rate_before'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-section-panel>
