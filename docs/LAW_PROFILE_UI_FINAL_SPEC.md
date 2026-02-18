# Law Profile Page — Final UI Specification (V2.0)

## Objective

Fully restructure the initiative detail page (`initiatives/show.blade.php`) so that a citizen can understand in under 10 seconds:

1. **What is this?** — Initiative type and title
2. **Who proposed it?** — Author with party link when applicable
3. **What happened?** — Status, duration, dates, and vote breakdown

---

## Page Layout Structure

```
┌─────────────────────────────────────────────────┐
│ Breadcrumbs: Painel > Iniciativas > XV/1/23     │
├─────────────────────────────────────────────────┤
│                                                 │
│ [Government Badge]  (if author_category=govt)   │
│ h1: Initiative Title                            │
│                                                 │
├──────────────────────┬──────────────────────────┤
│ Estado               │ Tipo                     │
│ 🟢 Aprovado (badge)  │ Projeto de Lei [?]       │
├──────────────────────┼──────────────────────────┤
│ Autor                │ Duração                  │
│ Grupo Parlamentar PS │ Aprovado em 49 dias      │
│ (linked to party)    │                          │
├──────────────────────┼──────────────────────────┤
│ Entrada              │ Votação Final            │
│ 09/10/2025           │ 27/11/2025               │
└──────────────────────┴──────────────────────────┘

┌─────────────────────────────────────────────────┐
│ h2: Votações                                    │
├─────────────────────────────────────────────────┤
│ Vote card 1 (most recent)                       │
│   [Summary bar]                                 │
│   [Party breakdown: A Favor / Contra / Abst.]   │
├─────────────────────────────────────────────────┤
│ Vote card 2 (earlier vote, if exists)           │
│   ...                                           │
└─────────────────────────────────────────────────┘
```

---

## Detailed Specifications

### 1. Breadcrumbs

**Replaces:** the current `← Voltar às iniciativas` back link.

- Format: `Painel > Iniciativas > {initiative.id}`
- Each segment is a link except the last (current page).
- Links: `Painel` → `route('dashboard')`, `Iniciativas` → `route('initiatives.index')`.
- Last segment shows the technical initiative ID (e.g., `XV/1/23`) in monospace.
- Separator: `>` or chevron icon, styled in muted text.
- Text size: `text-sm`, muted color (`text-sand-500`).

### 2. Title Area

- **Government badge:** If `author_category === 'government'`, show the existing `<x-government-badge />` component above or beside the title. Retained for quick visual signaling.
- **h1:** Initiative title. Font: `font-serif text-2xl sm:text-3xl font-bold`.
- The initiative ID no longer appears below the title (moved to breadcrumbs).

### 3. Metadata Summary Card

A single card containing a **3-row, 2-column grid** on desktop, stacking to **single column on mobile**.

**Visual style:** Standard card — `rounded-2xl border border-sand-200 bg-white dark:border-sand-800 dark:bg-sand-900`. Same as all other cards in the app.

#### Grid Cells

Each cell has:
- A label (small, uppercase, muted: `text-xs font-semibold uppercase tracking-wider text-sand-500`)
- An SVG icon next to the label (Heroicons, `h-4 w-4`, same style as parties/show metric cards)
- A value below the label

#### Row 1: Estado + Tipo

| Cell | Label | Icon | Value |
|------|-------|------|-------|
| **Estado** | `ESTADO` | Heroicon: check-circle (approved), x-circle (rejected), clock (in_progress) | `<x-status-badge>` component, size `md` |
| **Tipo** | `TIPO` | Heroicon: tag | `initiative_type_label` text + expandable `?` explanation |

#### Row 2: Autor + Duração

| Cell | Label | Icon | Value |
|------|-------|------|-------|
| **Autor** | `AUTOR` | Heroicon: user-group | `author_label` text. If `author_category === 'parliamentary_group'` and `author_party` is not null, wrap in `<a>` linking to `route('parties.show', strtolower(author_party))`. Otherwise plain text. |
| **Duração** | `DURAÇÃO` | Heroicon: clock | Status-specific phrasing (see below) |

#### Row 3: Entrada + Votação Final

| Cell | Label | Icon | Value |
|------|-------|------|-------|
| **Entrada** | `ENTRADA` | Heroicon: calendar | `entry_date->format('d/m/Y')` or `'Pendente'` if null |
| **Votação Final** | `VOTAÇÃO FINAL` | Heroicon: calendar-days | `final_vote_date->format('d/m/Y')` or `'Pendente'` if null |

#### Mobile Column Order

On mobile (`< sm`), cells stack in this order:
1. Estado
2. Tipo
3. Autor
4. Duração
5. Entrada
6. Votação Final

This is achieved with `order-{n}` Tailwind utilities on mobile, reverting on `sm:`.

---

### 4. Duration Cell — Status-Specific Phrasing

The duration cell always renders. Content depends on status:

| Status | Phrasing | Calculation |
|--------|----------|-------------|
| `approved` | `Aprovado em X dias` | `days_to_approval` field |
| `rejected` | `Rejeitado em X dias` | `entry_date` to `final_vote_date` diff |
| `in_progress` | `X dias em discussão` | `entry_date` to `now()` diff (existing `daysInProgress` accessor) |

**Edge case:** If the required dates are null (can't compute), show `—`.

Translation keys:
- `ui.initiatives.approved_in_days` → `Aprovado em :count dias`
- `ui.initiatives.rejected_in_days` → `Rejeitado em :count dias`
- `ui.initiatives.duration_pending` → `—`

---

### 5. Initiative Type Tooltip

Each of the 7 initiative types gets a custom explanation stored in `lang/pt/ui.php` under `ui.initiative_type_info.*`.

**Interaction pattern:** Always-visible `?` link/icon next to the type label. Clicking/tapping toggles an inline expandable text below the type value. Uses Alpine.js `x-data="{ open: false }"`.

**Collapsed state:** Type label + small `?` icon (Heroicon: question-mark-circle, `h-4 w-4`, `text-sand-400`).

**Expanded state:** A `text-sm text-sand-600` paragraph appears below the type value with the explanation.

#### Type Explanations (Portuguese)

| Type Key | Label | Explanation |
|----------|-------|-------------|
| `projeto_de_lei` | Projeto de Lei | Proposta de lei apresentada por deputados, grupos parlamentares ou grupos de cidadãos. Se aprovada, torna-se lei. |
| `proposta_de_lei` | Proposta de Lei | Proposta de lei apresentada pelo Governo. Se aprovada, torna-se lei. |
| `projeto_de_resolucao` | Projeto de Resolução | Proposta apresentada por deputados ou grupos parlamentares que não tem força de lei, mas expressa uma posição ou recomendação da Assembleia. |
| `proposta_de_resolucao` | Proposta de Resolução | Proposta de resolução apresentada pelo Governo, normalmente para ratificação de acordos internacionais. |
| `projeto_de_deliberacao` | Projeto de Deliberação | Proposta relativa ao funcionamento interno da Assembleia da República (ex: composição de comissões, calendário de trabalhos). |
| `inquerito_parlamentar` | Inquérito Parlamentar | Proposta de abertura de uma comissão de inquérito para investigar matérias de interesse público. |
| `apreciacao_parlamentar` | Apreciação Parlamentar | Processo que permite à Assembleia apreciar e eventualmente alterar ou cessar decretos-lei do Governo. |

**Mapping:** The `initiative_type_label` from the database maps to the key by normalizing: lowercase, replace spaces with `_`, strip accents. Example: `"Projeto de Resolução"` → `projeto_de_resolucao`. This mapping is done in the Blade template or via a helper.

---

### 6. Author Party Linking

- When `author_category === 'parliamentary_group'` and `author_party` is not null:
  - Render `author_label` as a link: `<a href="{{ route('parties.show', strtolower($initiative->author_party)) }}">`
  - Style: `text-republic-600 hover:text-republic-700 dark:text-republic-400 dark:hover:text-republic-300 underline`
- All other categories (`government`, `mixed`, `other`): render `author_label` as plain text.

**Verified:** All 10 MAIN_PARTIES acronyms resolve correctly as URL slugs with the existing route regex `[a-zA-Z-]+`.

---

### 7. Government Badge

- Retained from the current design.
- Positioned above the `<h1>` title, next to or above the status badge area.
- Only shown when `author_category === 'government'`.
- Note: The `author_type` field is being replaced by `author_category` in the display logic. The badge condition should use `author_category === 'government'`.

---

### 8. Votes Section

**No changes** to the existing votes section structure. All votes continue to be displayed vertically:

- Section heading: `h2 "Votações"` (font-serif)
- Each vote card shows:
  - Vote date + unanimous badge (if applicable)
  - Vote result text
  - Summary bar (`<x-vote-summary-bar>`)
  - Party breakdown grouped by position (A Favor, Contra, Abstenção)
- Empty state: centered message when no votes exist.

---

### 9. Visual Language

- **No emojis anywhere.** Use Heroicons SVGs and Tailwind-styled badges exclusively.
- **Icons:** `aria-hidden="true"` on all decorative SVGs in the metadata card.
- **Dark mode:** All new elements must support dark mode using the existing `dark:` variant pattern.
- **Typography:** Fraunces for headings, Source Sans for body — consistent with the rest of VotoClaro.

---

### 10. Translations

New keys to add to `lang/pt/ui.php`:

```php
'initiatives' => [
    // ... existing keys ...
    'type' => 'Tipo',
    'author' => 'Autor',
    'state' => 'Estado',
    'duration' => 'Duração',
    'entry' => 'Entrada',
    'final_vote' => 'Votação Final',
    'approved_in_days' => 'Aprovado em :count dias',
    'rejected_in_days' => 'Rejeitado em :count dias',
    'pending' => 'Pendente',
    'what_does_it_mean' => 'O que significa?',
],

'initiative_type_info' => [
    'projeto_de_lei' => '...',
    'proposta_de_lei' => '...',
    'projeto_de_resolucao' => '...',
    'proposta_de_resolucao' => '...',
    'projeto_de_deliberacao' => '...',
    'inquerito_parlamentar' => '...',
    'apreciacao_parlamentar' => '...',
],

'breadcrumbs' => [
    'dashboard' => 'Painel',
    'initiatives' => 'Iniciativas',
],
```

---

### 11. Files to Modify

| File | Changes |
|------|---------|
| `resources/views/pages/initiatives/show.blade.php` | Full restructure: breadcrumbs, title area, metadata card, keep votes section |
| `lang/pt/ui.php` | Add translation keys for metadata card, type tooltips, breadcrumbs, duration labels |
| `app/Models/Initiative.php` | Add `daysToRejection` accessor (entry_date to final_vote_date diff for rejected) |

### 12. Files NOT to Modify

- Controller (`InitiativeController@show`): All required fields already exist on the model. No new queries needed.
- Vote section components: `<x-vote-summary-bar>`, `<x-party-position-badge>` — unchanged.
- Layout: No changes to `layouts/app.blade.php`.

---

### 13. Tests

**Update:** `tests/Feature/InitiativeDetailTest.php`

New test cases:
- Breadcrumbs render with correct links and initiative ID
- Metadata card displays initiative_type_label
- Metadata card displays author_label
- Author links to party page when author_category is parliamentary_group
- Author is plain text when author_category is government
- Duration shows status-specific phrasing for approved/rejected/in_progress
- Type tooltip `?` element is present
- Date row shows 'Pendente' when dates are null
- Government badge appears when author_category is government
- Government badge absent when author_category is not government

---

### 14. Accessibility

- All decorative SVGs in the metadata card: `aria-hidden="true"`
- Breadcrumb `<nav>` element with `aria-label="Breadcrumbs"`
- Type tooltip expansion: focusable `<button>` with `aria-expanded` attribute
- Party author links: meaningful link text (the full `author_label`)

---

### 15. Mobile Responsive Summary

| Breakpoint | Metadata Grid | Behavior |
|------------|---------------|----------|
| `< sm` | Single column, 6 stacked cells (Estado → Tipo → Autor → Duração → Entrada → Votação Final) | Touch-friendly, outcome-first reading |
| `≥ sm` | 2-column, 3-row grid | Standard desktop layout |

---

### 16. Edge Cases

| Scenario | Behavior |
|----------|----------|
| `entry_date` is null | Entrada cell shows 'Pendente' |
| `final_vote_date` is null | Votação Final cell shows 'Pendente' |
| Both dates null | Date row still renders with 'Pendente' in both cells |
| `days_to_approval` is null on approved | Duration cell shows '—' |
| `author_party` is null on parliamentary_group (shouldn't happen) | Render author_label as plain text, no link |
| `initiative_type_label` doesn't match any tooltip key | Show type label without '?' button |
| Initiative has 0 votes | Votes section shows empty state message |
| Initiative has 3+ votes | All votes render vertically, no collapsing |
