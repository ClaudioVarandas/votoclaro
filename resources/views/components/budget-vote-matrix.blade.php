<div {{ $attributes->class(['overflow-hidden rounded-2xl border border-sand-200 bg-white dark:border-sand-800 dark:bg-sand-900']) }}>
    <p class="border-b border-sand-200 px-4 py-2 text-xs text-sand-500 sm:hidden dark:border-sand-800 dark:text-sand-400">{{ __('ui.budget.matrix.scroll_hint') }}</p>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <caption class="sr-only">{{ __('ui.budget.matrix.title') }}</caption>
            <thead>
                @if ($totals !== [])
                    <tr class="bg-sand-50 dark:bg-sand-950">
                        <th scope="col" class="sticky left-0 z-10 min-w-56 bg-sand-50 px-4 pt-4 pb-2 text-left sm:min-w-80 dark:bg-sand-950">
                            <span class="block text-xs font-semibold uppercase tracking-wider text-sand-700 dark:text-sand-300">{{ __('ui.budget.matrix.totals') }}</span>
                            <span class="mt-0.5 block text-xs font-normal text-sand-500 dark:text-sand-400">{{ trans_choice('ui.budget.matrix.totals_scope', $totalProposals(), ['count' => number_format($totalProposals(), 0, ',', '.')]) }}</span>
                        </th>
                        @foreach ($parties as $party)
                            <th scope="col" class="min-w-11 whitespace-nowrap px-1 pt-4 pb-2 text-center align-bottom text-xs font-semibold text-sand-700 dark:text-sand-300">{{ $party }}</th>
                        @endforeach
                    </tr>
                    @foreach ($totalRows() as $totalPosition => $totalRow)
                        <tr class="bg-sand-50 dark:bg-sand-950">
                            <th scope="row" class="sticky left-0 z-10 bg-sand-50 px-4 py-1.5 text-left text-sm font-normal text-sand-700 dark:bg-sand-950 dark:text-sand-300">
                                <span class="inline-flex items-center gap-2">
                                    <span @class(['inline-flex size-5 items-center justify-center rounded text-xs font-bold', $totalRow['classes']]) aria-hidden="true">{{ $totalRow['symbol'] }}</span>
                                    {{ $totalRow['label'] }}
                                </span>
                            </th>
                            @foreach ($parties as $party)
                                @php $count = $totals[$party][$totalPosition] ?? 0; @endphp
                                <td @class(['min-w-11 px-1 py-1.5 text-center', 'pb-4' => $loop->parent->last])>
                                    <span @class(['inline-flex h-7 min-w-9 items-center justify-center rounded-md px-1.5 text-sm font-semibold tabular-nums', $count > 0 ? $totalRow['classes'] : 'text-sand-400 dark:text-sand-600'])>{{ number_format($count, 0, ',', '.') }}</span>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endif
                <tr @class(['border-b border-sand-200 dark:border-sand-800', 'border-t-2' => $totals !== []])>
                    <th scope="col" class="sticky left-0 z-10 min-w-56 bg-white px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-sand-500 sm:min-w-80 dark:bg-sand-900 dark:text-sand-400">
                        {{ __('ui.budget.matrix.proposal') }}
                    </th>
                    @if ($totals === [])
                        @foreach ($parties as $party)
                            <th scope="col" class="min-w-11 whitespace-nowrap px-1 py-3 text-center text-xs font-semibold text-sand-700 dark:text-sand-300">{{ $party }}</th>
                        @endforeach
                    @else
                        <td colspan="{{ count($parties) }}"></td>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-sand-100 dark:divide-sand-800">
                @foreach ($proposals as $proposal)
                    <tr class="align-top">
                        <th scope="row" class="sticky left-0 z-10 bg-white px-4 py-3 text-left font-normal dark:bg-sand-900">
                            <a href="{{ $proposalUrl($proposal) }}" class="group block">
                                <span class="text-xs font-semibold text-sand-500 dark:text-sand-400">
                                    {{ $proposer($proposal) }} · <span class="font-mono">{{ $proposal->number }}</span>
                                    @if ($proposal->location())
                                        <span class="font-normal">· {{ $proposal->location() }}</span>
                                    @endif
                                </span>
                                <span class="mt-0.5 line-clamp-2 text-sand-900 group-hover:text-republic-700 group-hover:underline dark:text-sand-100 dark:group-hover:text-republic-400">{{ $proposal->subject() }}</span>
                                <x-budget-status-badge :status="$proposal->status" class="mt-1" />
                            </a>
                        </th>
                        @foreach ($parties as $party)
                            @php $position = $cell($proposal, $party); @endphp
                            <td class="min-w-11 px-1 py-3 text-center">
                                <span @class(['inline-flex size-8 shrink-0 items-center justify-center rounded-md text-sm font-bold', $cellClasses($position)]) title="{{ $party }}: {{ $label($position) }}">
                                    <span aria-hidden="true">{{ $symbol($position) }}</span>
                                    <span class="sr-only">{{ $party }}: {{ $label($position) }}</span>
                                </span>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
