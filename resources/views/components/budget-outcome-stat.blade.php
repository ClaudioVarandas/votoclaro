@if ($compact)
    <div {{ $attributes->class(['rounded-xl bg-sand-50 p-2 dark:bg-sand-950']) }}>
        <dt class="flex items-center justify-center gap-1.5 text-xs text-sand-500 dark:text-sand-400">
            <x-budget-outcome-icon :outcome="$outcome" class="p-0.5 [&_svg]:size-3" />
            {{ $label }}
        </dt>
        <dd @class(['mt-0.5 font-serif text-lg font-bold', $valueClasses()])>{{ $formattedValue() }}</dd>
    </div>
@else
    <div {{ $attributes->class(['flex flex-col rounded-2xl border border-sand-200 bg-white p-6 dark:border-sand-800 dark:bg-sand-900']) }}>
        <dt class="flex items-center gap-2 text-sm font-medium text-sand-500 dark:text-sand-400">
            <x-budget-outcome-icon :outcome="$outcome" />
            {{ $label }}
        </dt>
        <dd @class(['mt-2 font-serif text-3xl font-bold tracking-tight', $valueClasses()])>{{ $formattedValue() }}</dd>
    </div>
@endif
