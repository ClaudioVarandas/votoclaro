@props(['counts'])

@php
    $total = ($counts['favor'] ?? 0) + ($counts['contra'] ?? 0) + ($counts['abstencao'] ?? 0);
    $favorPct = $total > 0 ? round(($counts['favor'] / $total) * 100, 1) : 0;
    $contraPct = $total > 0 ? round(($counts['contra'] / $total) * 100, 1) : 0;
    $abstencaoPct = $total > 0 ? 100 - $favorPct - $contraPct : 0;
@endphp

<div {{ $attributes->class(['space-y-1.5']) }}>
    <div class="flex h-2.5 w-full overflow-hidden rounded-full bg-sand-200 dark:bg-sand-800">
        @if ($favorPct > 0)
            <div class="bg-republic-500 transition-all" style="width: {{ $favorPct }}%"></div>
        @endif
        @if ($contraPct > 0)
            <div class="bg-parliament-500 transition-all" style="width: {{ $contraPct }}%"></div>
        @endif
        @if ($abstencaoPct > 0)
            <div class="bg-amber-400 transition-all" style="width: {{ $abstencaoPct }}%"></div>
        @endif
    </div>
    <div class="flex justify-between text-xs text-sand-500 dark:text-sand-400">
        <span>{{ __('ui.position.favor') }} ({{ $counts['favor'] ?? 0 }})</span>
        <span>{{ __('ui.position.contra') }} ({{ $counts['contra'] ?? 0 }})</span>
        <span>{{ __('ui.position.abstencao') }} ({{ $counts['abstencao'] ?? 0 }})</span>
    </div>
</div>
