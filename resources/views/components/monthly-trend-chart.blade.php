@props(['trend'])

@if (count($trend) > 0)
    <div {{ $attributes->class(['space-y-1.5']) }}>
        @foreach ($trend as $month)
            @php
                $total = $month['total'];
                $favorPct = $total > 0 ? round(($month['favor'] / $total) * 100, 1) : 0;
                $contraPct = $total > 0 ? round(($month['contra'] / $total) * 100, 1) : 0;
                $abstencaoPct = $total > 0 ? 100 - $favorPct - $contraPct : 0;
            @endphp
            <div class="flex items-center gap-3">
                <span class="w-16 shrink-0 text-right font-mono text-xs text-sand-500 dark:text-sand-400">
                    {{ $month['month'] }}
                </span>
                <div class="flex h-5 flex-1 overflow-hidden rounded-full bg-sand-200 dark:bg-sand-800">
                    @if ($favorPct > 0)
                        <div class="bg-republic-500 transition-all" style="width: {{ $favorPct }}%" title="{{ __('ui.position.favor') }}: {{ $month['favor'] }}"></div>
                    @endif
                    @if ($contraPct > 0)
                        <div class="bg-parliament-500 transition-all" style="width: {{ $contraPct }}%" title="{{ __('ui.position.contra') }}: {{ $month['contra'] }}"></div>
                    @endif
                    @if ($abstencaoPct > 0)
                        <div class="bg-amber-400 transition-all" style="width: {{ $abstencaoPct }}%" title="{{ __('ui.position.abstencao') }}: {{ $month['abstencao'] }}"></div>
                    @endif
                </div>
                <span class="w-8 shrink-0 text-right text-xs text-sand-500 dark:text-sand-400">
                    {{ $total }}
                </span>
            </div>
        @endforeach
    </div>
@endif
