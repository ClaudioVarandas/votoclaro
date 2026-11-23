@props(['status', 'article' => false])

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', $status->color()]) }}>
    {{ $article ? $status->articleLabel() : $status->label() }}
</span>
