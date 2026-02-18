# Party Page – Voting History Section

## Objective

Allow citizens to see:

- Which initiatives the party voted on
- How it voted
- Whether initiative passed or failed


### Layout

```shell
-------------------------------------------------
| Como votou este partido                      |
-------------------------------------------------

| Iniciativa | Tipo | Data | Posição | Resultado |
-------------------------------------------------
| OE 2026    | Lei  | 27 Nov 2025 | 🟢 A Favor | 🟢 Aprovado |
| Apoio X    | Res. | 12 Jan 2026 | 🔴 Contra | 🔴 Rejeitado |

```


Blade Example

```php
<table class="min-w-full text-sm">

<thead class="bg-gray-100">
<tr>
    <th class="px-4 py-2 text-left">Iniciativa</th>
    <th class="px-4 py-2 text-left">Tipo</th>
    <th class="px-4 py-2 text-left">Data</th>
    <th class="px-4 py-2 text-left">Posição</th>
    <th class="px-4 py-2 text-left">Resultado</th>
</tr>
</thead>

<tbody>
@foreach($votes as $votePosition)
<tr class="border-b">
    <td class="px-4 py-2">
        <a href="{{ route('initiatives.show', $votePosition->vote->initiative->id) }}"
           class="text-blue-600 hover:underline">
            {{ $votePosition->vote->initiative->title }}
        </a>
    </td>

    <td class="px-4 py-2">
        {{ $votePosition->vote->initiative->initiative_type_label }}
    </td>

    <td class="px-4 py-2">
        {{ \Carbon\Carbon::parse($votePosition->vote->date)->format('d M Y') }}
    </td>

    <td class="px-4 py-2">
        @if($votePosition->position === 'favor')
            🟢 A Favor
        @elseif($votePosition->position === 'contra')
            🔴 Contra
        @else
            🟡 Abstenção
        @endif
    </td>

    <td class="px-4 py-2">
        {{ $votePosition->vote->result }}
    </td>
</tr>
@endforeach
</tbody>

</table>

```

---

## FINAL SPEC

### 1. Overview

Add a "Voting History" section to the party show page (`/partidos/{party}`) displaying a paginated, sortable, filterable table of all initiatives the party voted on (latest vote per initiative only, via `votes.is_latest`).

### 2. Data & Query

- **Source**: `VotePosition` → `Vote` (where `is_latest = true`) → `Initiative`
- **Eager load**: `vote.initiative` to prevent N+1
- **404 check**: Move `abort(404)` ABOVE the votes query so invalid party URLs don't trigger unnecessary DB hits
- **Pagination**: 20 rows per page, full page reload, using Laravel's `->paginate(20)`
- **Query param**: `page` (standard Laravel pagination)

### 3. Filtering — Position Tabs

- **Method**: Server-side via query parameter `?position=favor|contra|abstencao`
- **UI**: Tab-style buttons: `Todos (127) | A Favor (85) | Contra (30) | Abstenção (12)`
- **Counts**: Each tab displays the total count for that position (requires a count query per position)
- **Default**: "Todos" (no filter)
- **Interaction**: Clicking a tab reloads the page with `?position=X&page=1`

### 4. Sorting

- **Sortable columns**: Data (date), Resultado (result)
- **Method**: Server-side via query parameters `?sort=date|result&direction=asc|desc`
- **Default**: Date descending (newest first)
- **UX**: Clickable column headers with ▲/▼ arrow icons on the active sort column
- **First click**: Descending. Second click: Ascending. Third click: back to descending.

### 5. FormRequest Validation

- Create `ShowPartyRequest` FormRequest class
- Validate query parameters:
  - `position`: nullable, in:favor,contra,abstencao
  - `sort`: nullable, in:date,result
  - `direction`: nullable, in:asc,desc
  - `page`: nullable, integer, min:1

### 6. VoteResult Enum

- Create `App\Enums\VoteResult` with Portuguese string values matching the DB:
  - `case Aprovado = 'Aprovado'`
  - `case Rejeitado = 'Rejeitado'`
  - `case Prejudicado = 'Prejudicado'`
- Add methods: `color(): string` (Tailwind color class), `label(): string` (translation key)
- Cast `votes.result` to this enum in the Vote model
- **Prejudicado**: Displayed with a gray/muted badge style

### 7. Table Columns

| Column | Source | Notes |
|---|---|---|
| Iniciativa | `initiative.title` | Linked to `route('initiatives.show', $initiative->id)` |
| Tipo | `initiative.initiative_type_label` | Plain text |
| Data | `initiative.final_vote_date` | Format: `d M Y`. If null, show translated 'Pendente' label |
| Posição | `vote_position.position` | Tailwind pill badge (consistent with existing `<x-party-position-badge>`) |
| Resultado | `vote.result` | Tailwind pill badge via VoteResult enum. Aprovado=green, Rejeitado=red, Prejudicado=gray |

### 8. Responsive Layout

- **Desktop (sm+)**: Standard HTML table with all 5 columns
- **Mobile (<sm)**: Stacked card layout — each vote rendered as a card with:
  - Initiative title (linked) as card header
  - Key-value pairs below: Tipo, Data, Posição (pill badge), Resultado (pill badge)

### 9. Section Behavior

- **Collapsible**: Yes, using Alpine.js `x-data`, same pattern as Monthly Trend section
- **Default state**: Expanded (open by default)
- **Empty state**: When party has zero votes, show the section header + a message: "Sem registos de votação" (via translation key)
- **Section title**: Use translation key `__('ui.parties.voting_history')` — value: "Como votou este partido"

### 10. i18n Keys to Add

```
ui.parties.voting_history => "Como votou este partido"
ui.parties.voting_history_empty => "Sem registos de votação"
ui.parties.filter_all => "Todos"
ui.parties.sort_date => "Data"
ui.parties.sort_result => "Resultado"
ui.parties.date_pending => "Pendente"
ui.vote_result.Aprovado => "Aprovado"
ui.vote_result.Rejeitado => "Rejeitado"
ui.vote_result.Prejudicado => "Prejudicado"
```

### 11. Caching

- No caching for this feature at launch. Known tech debt — will be re-added once the feature is stable and data volume is assessed.

### 12. Files to Create/Modify

| Action | File |
|---|---|
| Create | `app/Enums/VoteResult.php` |
| Create | `app/Http/Requests/ShowPartyRequest.php` |
| Modify | `app/Http/Controllers/PartyController.php` — inject ShowPartyRequest, add filtering/sorting/pagination, move 404 check |
| Modify | `app/Models/Vote.php` — cast `result` to VoteResult enum |
| Modify | `resources/views/pages/parties/show.blade.php` — add voting history section |
| Modify | lang files — add translation keys |
| Create | Tests (PHPUnit) for: ShowPartyRequest validation, VoteResult enum, controller filtering/sorting/pagination, empty state |

### 13. Decisions Log

| Decision | Choice | Rationale |
|---|---|---|
| Pagination | 20 rows/page, full reload | Simple, no AJAX complexity |
| Sort | Server-side, Date + Resultado | URL-shareable, works with pagination |
| Filter | Server-side position tabs with counts | Accurate counts across pages, URL-shareable |
| Mobile | Stacked cards | Better UX than horizontal scroll on narrow screens |
| Enum values | Portuguese strings | Match DB values, no migration needed |
| Prejudicado | Include with gray badge | Transparent — users see all votes even non-decisive ones |
| Null dates | Show 'Pendente' label | Don't exclude in-progress initiatives, be transparent |
| Collapsible | Open by default | Voting history is the primary purpose of visiting the page |
| Badge style | Tailwind pills | Consistent with existing components |
| Cache | Deferred | Tech debt, re-add once stable |
| VoteParser perf | Leave as-is | Sync runs infrequently as CLI command |
| 404 order | Move above votes query | Correct control flow, avoid wasted DB query |
| Validation | FormRequest class | Follows project conventions from CLAUDE.md |
