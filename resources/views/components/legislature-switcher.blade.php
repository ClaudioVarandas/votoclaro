<div {{ $attributes->class('flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between') }}>
    <div class="flex items-center gap-2">
        <label for="legislature-switcher" class="text-sm font-medium text-sand-600 dark:text-sand-400">
            {{ __('ui.legislature.switcher_label') }}
        </label>
        <select id="legislature-switcher"
                x-data
                @change="window.location = $event.target.value"
                class="rounded-lg border border-sand-200 bg-white px-3 py-1.5 text-sm text-sand-900 focus:border-republic-500 focus:ring-republic-500 dark:border-sand-700 dark:bg-sand-900 dark:text-sand-100">
            @foreach ($options() as $option)
                <option value="{{ $option['url'] }}" @selected($option['selected'])>
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
    </div>

    @if (! $isViewingCurrent() && $active()?->isClosed())
        <p class="text-sm text-sand-500 dark:text-sand-400">
            {{ __('ui.legislature.closed_notice', [
                'sigla' => $active()->sigla,
                'start' => $active()->start_date?->format('Y'),
                'end' => $active()->end_date->format('Y'),
            ]) }}
        </p>
    @endif
</div>
