<ol {{ $attributes->class(['grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3']) }}>
    @foreach ($topics as $topic)
        <li>
            <a href="{{ $url($topic) }}"
               class="group flex h-full items-center gap-3 rounded-xl border border-sand-200 bg-sand-50 p-3.5 transition-all hover:-translate-y-0.5 hover:border-republic-300 hover:bg-white hover:shadow-sm dark:border-sand-800 dark:bg-sand-950 dark:hover:border-republic-700 dark:hover:bg-sand-900">
                <span @class([
                    'w-6 shrink-0 text-center font-serif text-lg font-bold tabular-nums',
                    'text-republic-600 dark:text-republic-400' => $loop->iteration <= 3,
                    'text-sand-400 dark:text-sand-500' => $loop->iteration > 3,
                ])>{{ $loop->iteration }}</span>
                <span class="inline-flex shrink-0 rounded-lg bg-white p-2 text-republic-700 shadow-sm ring-1 ring-sand-200 dark:bg-sand-900 dark:text-republic-400 dark:ring-sand-800">
                    <x-budget-theme-icon :theme="$topic->label" class="size-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-base font-semibold text-sand-900 group-hover:text-republic-700 dark:text-sand-100 dark:group-hover:text-republic-400">{{ $topic->label }}</span>
                    <span class="mt-0.5 block text-sm text-sand-500 dark:text-sand-400">
                        <span class="font-semibold text-republic-700 dark:text-republic-400">{{ $figures($topic)[0] }}</span>
                        <span aria-hidden="true">·</span> {{ $figures($topic)[1] }}
                    </span>
                    @if ($showsDivision())
                        <x-progress-bar :share="$topic->division ?? 0" color="parliament" class="mt-2" />
                    @endif
                </span>
            </a>
        </li>
    @endforeach
</ol>
