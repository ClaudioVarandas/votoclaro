@props(['status', 'article' => false, 'closed' => false])

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', $status->color($closed)]) }}>
    {{ $article ? $status->articleLabel($closed) : $status->label($closed) }}
</span>
