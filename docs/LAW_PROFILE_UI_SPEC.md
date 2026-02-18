I've improved the backend model.
Now the UI must reflect that structure.

Right now your Law Profile page probably shows only:

- Title
- Status badge
- 
That’s not enough anymore.

We now have:

- initiative_type_label
- author_label
- author_category
- author_party

So let’s upgrade the Law Profile page into something clearer and more citizen-friendly.

📄 Law Profile Page — Updated UI Spec (Markdown)


Law Profile Page – UI Specification (V1.1)

Objective

Improve clarity by explicitly showing:

- Initiative Type
- Author
- Status
- Timeline summary

The goal is for a citizen to understand in under 10 seconds:

What is this?
Who proposed it?
What happened?

Page Layout Structure

```shell
-------------------------------------------------
| Breadcrumbs                                   |
-------------------------------------------------

| Initiative Title                              |
-------------------------------------------------

| 🏷 Tipo: Projeto de Resolução                 |
| 👤 Autor: Grupo Parlamentar L                 |
| 🟢 Estado: Aprovado                           |
| ⏱ Aprovado em 49 dias                         |
-------------------------------------------------

| Timeline                                      |
-------------------------------------------------

| Entrada: 09 Oct 2025                          |
| Votação Final: 27 Nov 2025                    |

[ Ver detalhes ▼ ]

-------------------------------------------------
| Votação                                       |
-------------------------------------------------

| Partido | Posição                             |
| PSD     | 🟢 A Favor                          |
| PCP     | 🔴 Contra                           |
| IL      | 🟡 Abstenção                        |
-------------------------------------------------

```

Required Backend Fields (Already Implemented)

The Law Profile page must use:


| Field                   | Source                        |
| ----------------------- | ----------------------------- |
| `initiative_type_label` | IniDescTipo                   |
| `author_label`          | Derived from author detection |
| `status`                | Derived from vote             |
| `days_to_approval`      | Calculated                    |
| `entry_date`            | Extracted from Entrada event  |
| `final_vote_date`       | Latest vote                   |


Blade Implementation Example

Inside your show.blade.php:

```php

<h1 class="text-2xl font-bold mb-4">
    {{ $initiative->title }}
</h1>

<div class="bg-white shadow rounded-xl p-6 space-y-2">

    <div>
        <span class="font-semibold">🏷 Tipo:</span>
        {{ $initiative->initiative_type_label }}
    </div>

    <div>
        <span class="font-semibold">👤 Autor:</span>
        {{ $initiative->author_label }}
    </div>

    <div>
        <span class="font-semibold">Estado:</span>

        @if($initiative->status === 'approved')
            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">
                🟢 Aprovado
            </span>
        @elseif($initiative->status === 'rejected')
            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm">
                🔴 Rejeitado
            </span>
        @else
            <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm">
                🟡 Em curso
            </span>
        @endif
    </div>

    @if($initiative->days_to_approval)
        <div>
            ⏱ Aprovado em {{ $initiative->days_to_approval }} dias
        </div>
    @endif

</div>

```


UX Improvements (Optional but Recommended)
1️⃣ Add Tooltip for "Tipo"

Some citizens don’t know what:

- Projeto de Lei
- Projeto de Resolução
- Proposta de Lei

mean.

You can add Alpine tooltip:

```html
<span x-data="{ open: false }" class="relative">
    <button @mouseenter="open = true" @mouseleave="open = false" class="underline text-sm">
        O que significa?
    </button>

    <div x-show="open"
         class="absolute bg-gray-800 text-white text-xs p-2 rounded mt-2 w-64">
        {{ $initiative->initiative_type_label }} é um tipo de iniciativa parlamentar.
    </div>
</span>

```

2️⃣ Visual Hierarchy Adjustment

Instead of stacking everything vertically, you may want:

Two-column layout on desktop:

```
[ Tipo ]            [ Estado ]
[ Autor ]           [ Dias ]

```

Using Tailwind:

```html
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

```


Makes it feel structured.

Why This Matters

Before:

Title + green badge

After:

This is a Projeto de Resolução proposed by Grupo Parlamentar L. It was approved in 49 days.

That’s comprehension.

That’s clarity.

That’s civic UX.
