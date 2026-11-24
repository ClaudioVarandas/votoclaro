<x-section-panel id="agreement-title" :title="__('ui.budget.agreement.title')" :intro="__('ui.budget.agreement.intro')" icon="users">
    <div class="space-y-4">
        {{-- Wide screens: the whole grid --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="mx-auto border-separate border-spacing-1 text-sm">
                <caption class="sr-only">{{ __('ui.budget.agreement.title') }}</caption>
                <thead>
                    <tr>
                        <td></td>
                        @foreach ($parties() as $column)
                            <th scope="col" class="min-w-14 px-1 pb-1 text-center text-xs font-semibold text-sand-700 dark:text-sand-300">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($parties() as $row)
                        <tr>
                            <th scope="row" class="pr-2 text-right text-xs font-semibold whitespace-nowrap text-sand-700 dark:text-sand-300">{{ $row }}</th>
                            @foreach ($parties() as $column)
                                <td class="p-0">
                                    @if ($row === $column)
                                        <span class="flex h-11 items-center justify-center rounded-md bg-sand-50 text-sand-300 dark:bg-sand-950 dark:text-sand-700" aria-hidden="true">·</span>
                                    @elseif ($apartCount($row, $column) === 0)
                                        <span @class(['flex h-11 items-center justify-center rounded-md font-semibold tabular-nums', $shadeClass($row, $column)]) title="{{ $describe($row, $column) }}">{{ $percent($row, $column) }}</span>
                                    @else
                                        <a href="{{ $url($row, $column) }}" title="{{ $describe($row, $column) }}" aria-label="{{ $describe($row, $column) }}"
                                           @class(['flex h-11 items-center justify-center rounded-md font-semibold tabular-nums transition hover:ring-2 hover:ring-sand-900/30 dark:hover:ring-sand-100/40', $shadeClass($row, $column)])>{{ $percent($row, $column) }}</a>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Phones: one party at a time --}}
        <div class="space-y-3 md:hidden" x-data="{ party: @js($parties()[0]) }">
            <label for="agreement-party" class="block text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.budget.agreement.pick') }}</label>
            <select id="agreement-party" x-model="party" class="w-full rounded-xl border border-sand-200 bg-sand-50 px-3 py-2.5 text-sm text-sand-900 focus:border-republic-500 focus:outline-none focus:ring-1 focus:ring-republic-500 dark:border-sand-700 dark:bg-sand-950 dark:text-sand-100">
                @foreach ($parties() as $party)
                    <option value="{{ $party }}">{{ $party }}</option>
                @endforeach
            </select>
            @foreach ($parties() as $party)
                <ol x-show="party === @js($party)" @if (! $loop->first) x-cloak @endif class="divide-y divide-sand-100 dark:divide-sand-800">
                    @foreach ($closestTo($party) as $other)
                        <li>
                            <a href="{{ $url($party, $other) }}" class="flex items-center gap-3 py-2.5">
                                <span class="w-16 shrink-0 text-sm font-bold text-sand-900 dark:text-sand-100">{{ $other }}</span>
                                <span class="min-w-0 flex-1">
                                    <x-progress-bar :share="($rate($party, $other) ?? 0) / 100" thick />
                                    <span class="mt-1 block text-xs text-sand-500 dark:text-sand-400">{{ $describe($party, $other) }}</span>
                                </span>
                                <span class="w-12 shrink-0 text-right font-semibold tabular-nums text-republic-700 dark:text-republic-400">{{ $percent($party, $other) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-sand-500 dark:text-sand-400">
            <span class="hidden items-center gap-1.5 md:inline-flex">
                {{ __('ui.budget.agreement.legend_low') }}
                @foreach ($legendShades() as $shade)
                    <span @class(['inline-block size-4 rounded', $shade]) aria-hidden="true"></span>
                @endforeach
                {{ __('ui.budget.agreement.legend_high') }}
            </span>
            <span>{{ __('ui.budget.agreement.how') }}</span>
        </div>
    </div>
</x-section-panel>
