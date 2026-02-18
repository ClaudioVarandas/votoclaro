# VotoClaro.pt — Frontend Specification v1.0

## 1. Design Philosophy

**Aesthetic Direction: Warm Civic Clarity**

VotoClaro is not a government portal. It is not a news site. It is a *clarity tool* — and its design must embody that mission. The visual language draws from editorial design (think Monocle, Works That Work) crossed with civic warmth (think a well-designed public library).

The memorable quality: **data presented with the warmth and care of a hand-written letter**. Parliamentary data is cold and bureaucratic by nature — VotoClaro makes it feel human and approachable without dumbing it down.

**Core design tenets:**
- Comprehension in 60 seconds (dashboard)
- Warmth over sterility — charcoal and amber, not blue and gray
- Data speaks first — no hero sections, no marketing copy on data pages
- Respect the source — full official titles, real parliamentary terms, tooltips for context
- Accessible by default — every color carries an icon, every interaction has a keyboard path

---

## 2. Technical Stack

| Layer | Technology | Notes |
|-------|-----------|-------|
| Backend | Laravel 12 (PHP 8.4) | Server-rendered, no SPA |
| Templates | Blade | Using `__()` / lang files for i18n-readiness |
| Interactivity | Alpine.js 3 | Expandable sections, load-more, tooltips, dark mode toggle |
| Styling | TailwindCSS 4 | Custom theme tokens, dark mode via class strategy |
| Database | MySQL | Daily import from Parlamento.pt JSON API |
| Build | Vite | Asset compilation |

**Language:** Portuguese only in V1. All user-facing strings must use `__('key')` or `@lang('key')` in Blade templates, with translation files in `lang/pt/`. This makes future i18n trivial.

---

## 3. Typography

**Display / Headings:** [Fraunces](https://fonts.google.com/specimen/Fraunces) — a soft serif with optical size axis. Warm, characterful, distinctly *not* institutional. Used for page titles, metric numbers, and section headings.

**Body / UI:** [Source Sans 3](https://fonts.google.com/specimen/Source+Sans+3) — highly legible, warm humanist sans-serif. Excellent for data tables, labels, and body text. Subtle enough to let Fraunces shine.

**Monospace (data/IDs):** [JetBrains Mono](https://fonts.google.com/specimen/JetBrains+Mono) — for initiative IDs, dates in technical contexts only.

### Type Scale

| Role | Font | Tailwind Class | Size |
|------|------|---------------|------|
| Page title | Fraunces | `text-3xl font-bold` | 30px |
| Metric number | Fraunces | `text-5xl font-extrabold` | 48px |
| Section header | Fraunces | `text-xl font-semibold` | 20px |
| Card title | Source Sans 3 | `text-lg font-semibold` | 18px |
| Body text | Source Sans 3 | `text-base` | 16px |
| Caption / label | Source Sans 3 | `text-sm text-stone-500` | 14px |
| Badge text | Source Sans 3 | `text-xs font-medium uppercase tracking-wide` | 12px |

---

## 4. Color System

### Warm Neutral Palette

The palette deliberately avoids blue (institutional), purple (party association), and primary red/green in large areas (political connotation). Status colors are used sparingly and always paired with icons.

#### Light Mode

| Token | Hex | Usage |
|-------|-----|-------|
| `--color-charcoal` | `#2D2A26` | Primary text, headings |
| `--color-warm-gray` | `#6B6560` | Secondary text |
| `--color-stone` | `#A8A29E` | Tertiary text, borders |
| `--color-cream` | `#FAF9F7` | Page background |
| `--color-warm-white` | `#FFFFFF` | Card backgrounds |
| `--color-amber` | `#D97706` | Primary accent (links, active states, CTA) |
| `--color-coral` | `#E8725A` | Secondary accent (hover states, highlights) |
| `--color-amber-light` | `#FEF3C7` | Accent background tint |

#### Dark Mode

| Token | Hex | Usage |
|-------|-----|-------|
| `--color-dark-bg` | `#1C1917` | Page background (stone-900) |
| `--color-dark-card` | `#292524` | Card backgrounds (stone-800) |
| `--color-dark-text` | `#F5F5F4` | Primary text (stone-100) |
| `--color-dark-secondary` | `#A8A29E` | Secondary text (stone-400) |
| `--color-dark-border` | `#44403C` | Borders (stone-700) |
| `--color-amber-dark` | `#F59E0B` | Accent (slightly brighter for contrast) |

#### Status Colors (Always Paired with Icons)

| Status | Color | Icon | Light BG | Dark BG |
|--------|-------|------|----------|---------|
| Aprovado | `#16A34A` (green-600) | Checkmark circle | `#F0FDF4` (green-50) | `#052E16` (green-950) |
| Rejeitado | `#DC2626` (red-600) | X circle | `#FEF2F2` (red-50) | `#450A0A` (red-950) |
| Em Curso | `#CA8A04` (yellow-600) | Clock | `#FEFCE8` (yellow-50) | `#422006` (yellow-950) |

#### Government Badge

| Element | Light | Dark |
|---------|-------|------|
| Background | `#DBEAFE` (blue-100) | `#1E3A5F` |
| Text | `#1E40AF` (blue-800) | `#93C5FD` (blue-300) |
| Icon | Building/institution icon | Same |

---

## 5. Layout & Spacing

### Grid System

```
Container: max-w-6xl mx-auto px-6
```

- Dashboard: CSS Grid — 2-column metric cards, full-width latest votes
- Initiative listing: Sidebar (280px fixed) + main content area
- Initiative detail: Single column, max-w-4xl for readability
- Party listing: Responsive card grid (3 cols desktop, 2 tablet, 1 mobile)

### Spacing Scale

| Name | Value | Usage |
|------|-------|-------|
| `space-section` | `py-12` | Between major page sections |
| `space-card-gap` | `gap-6` | Between cards in a grid |
| `space-card-inner` | `p-6` | Card internal padding |
| `space-element` | `mb-4` | Between elements within a section |
| `space-tight` | `mb-2` | Between tightly related items |

### Card Style

```
Light: bg-white rounded-2xl shadow-sm border border-stone-200/60 p-6
Dark:  bg-stone-800 rounded-2xl shadow-sm border border-stone-700/60 p-6
Hover: shadow-md transition-shadow duration-200
```

Cards use `rounded-2xl` (not `rounded-xl`) for a softer, friendlier feel consistent with the civic aesthetic.

---

## 6. Component Specifications

### 6.1 Status Badge

Accessible badge combining color, icon, and text label.

```
Structure: [icon] [text]
Sizes: sm (inline in tables), md (default), lg (detail page header)

Approved/sm: bg-green-50 text-green-700 px-2.5 py-0.5 rounded-full text-xs font-medium
             icon: heroicon-check-circle (w-3.5 h-3.5)
             label: "Aprovado"

Rejected/sm: bg-red-50 text-red-700 ...
             icon: heroicon-x-circle
             label: "Rejeitado"

In Progress/sm: bg-yellow-50 text-yellow-700 ...
                icon: heroicon-clock
                label: "Em Curso"
```

Dark mode variants swap to `-950` backgrounds and `-300` text colors.

### 6.2 Government Badge

```
Structure: [building-icon] "Governo"
Style: bg-blue-100 text-blue-800 px-2.5 py-0.5 rounded-full text-xs font-medium
Dark:  bg-blue-900/40 text-blue-300
Icon:  heroicon-building-library (w-3.5 h-3.5)
```

Appears on: dashboard latest votes, initiative listing cards, initiative detail header.

### 6.3 Tooltip (Parliamentary Terms)

Alpine.js powered. Triggered on hover (desktop) and tap (mobile).

```
Trigger: Dotted underline on the term (border-b border-dotted border-stone-400 cursor-help)
Tooltip: bg-charcoal text-white text-sm rounded-lg px-3 py-2 shadow-lg max-w-xs
         Arrow pointing to trigger element
         z-50, positioned above trigger
```

Example terms requiring tooltips:
- "Projeto de Lei" → "Proposta legislativa apresentada por Deputados ou grupos parlamentares"
- "Proposta de Lei" → "Proposta legislativa apresentada pelo Governo"
- "Votação final global" → "Última votação sobre a totalidade de uma iniciativa"

### 6.4 Dark Mode Toggle

Position: Right side of top navigation bar.

```
Alpine.js component:
- x-data="{ dark: localStorage.getItem('theme') === 'dark' }"
- x-init="$watch('dark', val => { localStorage.setItem('theme', val ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', val) })"
- Toggle button: sun icon ↔ moon icon with smooth transition
- Button style: p-2 rounded-lg hover:bg-stone-100 dark:hover:bg-stone-800
```

Tailwind config must use `darkMode: 'class'` strategy.

### 6.5 Load More Button

Used on initiative listing page.

```
Alpine.js component:
- Fetches next page via AJAX (fetch API to a Blade partial endpoint)
- Appends results to existing list
- Button disappears when no more results

Style: w-full py-3 text-amber-700 font-semibold border-2 border-amber-200 rounded-xl
       hover:bg-amber-50 hover:border-amber-300 transition-colors
       dark:text-amber-400 dark:border-amber-800 dark:hover:bg-amber-900/20
Label: "Carregar mais iniciativas"
Loading state: spinner icon + "A carregar..."
```

### 6.6 Vertical Timeline

Used on initiative detail page.

```
Structure:
┌─ ● Entry date
│     Label + date
│
├─ ● First vote (if exists)
│     Label + date + result
│
├─ ● Final vote (if exists)
│     Label + date + result
│
└─ ● Publication (if exists)
      Label + date

Visual:
- Connecting line: w-0.5 bg-stone-200 dark:bg-stone-700, absolute left-[11px]
- Dots: w-6 h-6 rounded-full border-2
  - Completed step: bg-amber-500 border-amber-500 (filled)
  - Current step: bg-white border-amber-500 (ring effect)
  - Future step: bg-stone-100 border-stone-300 (muted)
- Date: text-sm text-stone-500
- Label: text-base font-medium text-charcoal
- Detail expandable via Alpine.js x-show with transition
```

### 6.7 Stacked Vote Summary Bar

Horizontal bar showing proportions of Favor / Contra / Abstention.

```
Structure:
[=======GREEN=======|===RED===|==YELLOW==]
  62% Favor          24% Contra  14% Abst.

- Container: h-3 rounded-full overflow-hidden flex
- Segments: proportional width via inline style (width: X%)
  - Favor: bg-green-500
  - Contra: bg-red-500
  - Abstention: bg-yellow-500
- Labels below: flex justify-between, text-sm
- Minimum segment width: 2% (to remain visible)
```

### 6.8 Party Voting Table (with Split Vote Support)

```
Table structure:
| Partido | Posicao            |  (expand icon) |
|---------|--------------------|----------------|
| PS      | [green] Favor      |                |
| PSD     | [green] Favor      |                |
| CH      | [red] Contra       |   ▼ (expand)   |  ← has split vote
| IL      | [yellow] Abstencao |                |

Expanded split row (Alpine.js x-show):
| CH breakdown:                                |
|   10 Favor · 2 Contra · 1 Abstencao         |

Table style:
- Header: text-xs uppercase tracking-wider text-stone-500 border-b border-stone-200
- Rows: py-3 border-b border-stone-100 hover:bg-stone-50
- Expand button: heroicon-chevron-down, rotates 180deg when open
- Expanded detail: bg-stone-50 dark:bg-stone-800/50 px-6 py-3 text-sm
```

Mobile: Table scrolls horizontally. Scroll hint shadow on right edge:

```
Container: overflow-x-auto
Right shadow hint: pseudo-element with gradient from transparent to bg-cream
```

### 6.9 Sidebar Filters (Initiative Listing)

```
Width: 280px (desktop), full-width slide-in (mobile via Alpine.js)

Components:
1. Text search input
   - Icon: heroicon-magnifying-glass inside input
   - Style: w-full pl-10 pr-4 py-2.5 rounded-xl border border-stone-200
            focus:border-amber-400 focus:ring-2 focus:ring-amber-100
   - Placeholder: "Pesquisar iniciativas..."
   - Debounced input (300ms) via Alpine.js

2. Status filter pills
   - Horizontal row of toggle pills
   - Inactive: bg-stone-100 text-stone-600
   - Active: bg-amber-100 text-amber-800 ring-1 ring-amber-300
   - Labels: "Aprovado", "Rejeitado", "Em Curso"

3. Author type filter
   - Pills: "Governo", "Partido", "Deputado", "Outro"
   - Same active/inactive styling as status

Mobile trigger: Floating filter button (bottom-right) opens sidebar as overlay
```

---

## 7. Page Specifications

### 7.1 Dashboard (`/`)

**Route:** `GET /` — `DashboardController@index`

**Layout:**

```
[Top Navigation Bar                                    ]

[Metrics Row - 2 columns]
┌─────────────────────────┐ ┌─────────────────────────┐
│ Total de Iniciativas    │ │ Taxa de Aprovacao Gov.   │
│ ████ 1,247              │ │ ████ 78%                 │
│                         │ │                          │
│ 🟢 62% · 🔴 28% · 🟡10% │ │ 🟢 78% · 🔴 18% · 44 dias│
│                         │ │ medio                    │
└─────────────────────────┘ └─────────────────────────┘

[Updated daily badge - subtle, right-aligned]

[Latest Votes - Full width]
┌──────────────────────────────────────────────────────┐
│ Ultimas Votacoes                          Ver todas →│
│──────────────────────────────────────────────────────│
│ [GOV] Initiative Title...        🟢 Aprovado  12 Fev│
│ Initiative Title Two...          🔴 Rejeitado 11 Fev│
│ [GOV] Another Initiative...      🟡 Em Curso  10 Fev│
│ ...                                                  │
│ (10 items)                                           │
└──────────────────────────────────────────────────────┘

[Footer                                                ]
```

**Metric cards detail:**

Total Initiatives card:
- Large number (Fraunces, text-5xl)
- Below: Three mini-badges in a row showing status distribution
- Each mini-badge: colored dot + percentage + label

Government Approval Rate card:
- Large percentage (Fraunces, text-5xl) — approval rate
- Below: % rejected, average days to approval
- "dias em media para aprovacao" label

**Latest Votes list:**
- 10 items, ordered by vote date descending
- Each row: [Government badge if applicable] [Title] [Status badge] [Date]
- Title links to initiative detail page
- "Ver todas" link goes to `/xvi/iniciativas`

**Data queries (computed on load):**
```
- Total initiatives: Initiative::count()
- Status breakdown: Initiative::selectRaw('status, count(*)')->groupBy('status')
- Government stats: Initiative::where('author_type', 'Government')->selectRaw(...)
- Latest votes: Initiative::with('votes')->latest('final_vote_date')->take(10)
```

---

### 7.2 Initiative Listing (`/{legislature}/iniciativas`)

**Route:** `GET /{legislature}/iniciativas` — `InitiativeController@index`

**Layout:**

```
[Top Navigation Bar                                    ]

[Page Title: "Iniciativas Parlamentares"]

┌──────────┐ ┌────────────────────────────────────────┐
│ SIDEBAR  │ │ INITIATIVE CARDS                       │
│          │ │                                        │
│ [Search] │ │ ┌────────────────────────────────────┐ │
│          │ │ │ [GOV] Full initiative title here... │ │
│ Status:  │ │ │ 🟢 Aprovado · 12 Fev 2026         │ │
│ [pills]  │ │ └────────────────────────────────────┘ │
│          │ │                                        │
│ Autor:   │ │ ┌────────────────────────────────────┐ │
│ [pills]  │ │ │ Full initiative title here that     │ │
│          │ │ │ can be very long and wraps...       │ │
│          │ │ │ 🔴 Rejeitado · 8 Fev 2026          │ │
│          │ │ └────────────────────────────────────┘ │
│          │ │                                        │
│          │ │ [  Carregar mais iniciativas  ]        │
└──────────┘ └────────────────────────────────────────┘
```

**URL behavior:**
- Filters reflected in URL: `?status=approved&author=Government`
- Pagination NOT reflected in URL (frontend-only via Alpine.js)

**Initiative card contents:**
- Full original title (no truncation)
- Government badge (if author_type === 'Government')
- Status badge (color + icon + text)
- Entry date
- Final vote date (if exists)
- Duration badge for in-progress: "Em curso ha X dias"
- Entire card is clickable, links to detail page

**Pagination:**
- Initial load: 20 initiatives
- "Load more" fetches next 20 via AJAX to a Blade partial
- Button hidden when all results loaded
- Alpine.js manages appending and button state

---

### 7.3 Initiative Detail (`/{legislature}/iniciativas/{id}-{slug}`)

**Route:** `GET /{legislature}/iniciativas/{id}-{slug}` — `InitiativeController@show`

**Layout:**

```
[Top Navigation Bar                                    ]

┌──────────────────────────────────────────────────────┐
│ HEADER                                               │
│                                                      │
│ [GOV badge]                                          │
│ Full Initiative Title Displayed in Fraunces Font     │
│                                                      │
│ 🟢 Aprovado  ·  Entrada: 12 Jan 2025                │
│               ·  Votacao final: 15 Jul 2025          │
│               ·  184 dias para aprovacao             │
│                                                      │
│ (or for in-progress:)                                │
│ 🟡 Em Curso  ·  Entrada: 12 Jan 2025                │
│               ·  Em curso ha 401 dias                │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│ TIMELINE                                             │
│                                                      │
│ ● Entrada                          12 Jan 2025      │
│ │                                                    │
│ ● Primeira votacao                 28 Mar 2025       │
│ │ Aprovado na generalidade                           │
│ │ [ Mostrar detalhes ▼ ]                             │
│ │                                                    │
│ ● Votacao final global             15 Jul 2025       │
│ │ Aprovado                                           │
│ │                                                    │
│ ● Publicacao                       20 Jul 2025       │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│ VOTING BREAKDOWN                                     │
│                                                      │
│ Resultado da Votacao                                 │
│                                                      │
│ [=======GREEN========|===RED===|=YELLOW=]            │
│ 62% Favor     24% Contra    14% Abstencao           │
│                                                      │
│ ┌──────────┬──────────────────┬────────┐             │
│ │ Partido  │ Posicao          │        │             │
│ ├──────────┼──────────────────┼────────│             │
│ │ PS       │ 🟢 Favor         │        │             │
│ │ PSD      │ 🟢 Favor         │        │             │
│ │ CH       │ 🔴 Contra        │   ▼    │ ← split    │
│ │ IL       │ 🟡 Abstencao     │        │             │
│ │ PCP      │ 🔴 Contra        │        │             │
│ └──────────┴──────────────────┴────────┘             │
└──────────────────────────────────────────────────────┘

(or if no votes:)
┌──────────────────────────────────────────────────────┐
│ Resultado da Votacao                                 │
│                                                      │
│ [clock icon]                                         │
│ Esta iniciativa ainda nao chegou a votacao.          │
│ Pode estar em analise em comissao.                   │
└──────────────────────────────────────────────────────┘
```

**Split vote expansion (Alpine.js):**
- Chevron icon rotates on click
- Expanded row slides down with `x-show` + `x-transition`
- Shows: "10 Favor · 2 Contra · 1 Abstencao"

**SEO for this page:**
- `<title>`: Initiative title + " | VotoClaro"
- `<meta description>`: "Status: Aprovado. Votacao final: 15 Jul 2025. Veja como cada partido votou."
- JSON-LD: LegislativeEvent schema
- Open Graph: title, description, status image

---

### 7.4 Party Listing (`/{legislature}/partidos`)

**Route:** `GET /{legislature}/partidos` — `PartyController@index`

**Layout:**

```
[Top Navigation Bar                                    ]

[Page Title: "Partidos Parlamentares"]

┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│ PS          │ │ PSD         │ │ CH          │
│             │ │             │ │             │
│ 120 votos   │ │ 118 votos   │ │ 95 votos    │
│ 🟢 62%      │ │ 🟢 58%      │ │ 🟢 34%      │
│ 🔴 28%      │ │ 🔴 30%      │ │ 🔴 52%      │
│ 🟡 10%      │ │ 🟡 12%      │ │ 🟡 14%      │
│             │ │             │ │             │
│ Gov: 78%    │ │ Gov: 72%    │ │ Gov: 22%    │
└─────────────┘ └─────────────┘ └─────────────┘

(3 cols desktop, 2 tablet, 1 mobile)
```

**Card contents:**
- Party name (acronym) as heading
- Total votes participated
- Three-line breakdown: % Favor, % Contra, % Abstention (with status colors/icons)
- Government alignment percentage as a subtle bottom metric
- Entire card links to party detail page

---

### 7.5 Party Detail (`/{legislature}/partidos/{party}`)

**Route:** `GET /{legislature}/partidos/{party}` — `PartyController@show`

**Layout:**

```
[Top Navigation Bar                                    ]

┌──────────────────────────────────────────────────────┐
│ PARTY HEADER                                         │
│                                                      │
│ PS                                                   │
│                                                      │
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐│
│ │ 120      │ │ 62%      │ │ 28%      │ │ 10%      ││
│ │ Votos    │ │ 🟢 Favor  │ │ 🔴 Contra│ │ 🟡 Abst. ││
│ └──────────┘ └──────────┘ └──────────┘ └──────────┘│
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│ ALINHAMENTO COM O GOVERNO                            │
│                                                      │
│ 78%                                                  │
│ das iniciativas do Governo votadas a favor           │
│                                                      │
│ [ Mostrar evolucao mensal ▼ ]                        │
│                                                      │
│ (expanded: monthly trend chart)                      │
│ ┌────────────────────────────────────────────────┐   │
│ │  Jan  Feb  Mar  Apr  May  Jun  Jul  Aug  ...   │   │
│ │  ██   ██   ██   ██   ██   ██   ██   ██        │   │
│ │  82%  75%  90%  68%  77%  80%  85%  72%       │   │
│ └────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────┘
```

**Alignment trend chart:**
- Simple bar chart (CSS only — no JS charting library)
- Each bar: `div` with dynamic `height` via inline style
- X-axis: month abbreviations
- Y-axis: implied by bar height (no explicit axis needed)
- Bar color: amber accent
- Container: expandable via Alpine.js `x-show`
- Height: max 200px

---

### 7.6 About Page (`/sobre`)

**Route:** `GET /sobre` — `Route::view('sobre', 'pages.about')`

**Content (static, i18n strings):**
1. **O que e o VotoClaro** — Mission statement, transparency principles
2. **Como funciona o Parlamento** — Brief explainer on legislature structure, initiative types, voting process
3. **Como ler os dados** — Guide to understanding statuses, voting breakdowns, alignment metrics
4. **Fonte dos dados** — Attribution to Parlamento.pt, data freshness explanation
5. **Contacto** — Feedback/contact information

**Layout:** Single column, max-w-3xl, generous spacing between sections. Fraunces headings, Source Sans body. No cards — flowing editorial layout.

---

## 8. Navigation

### Top Navigation Bar

```
┌──────────────────────────────────────────────────────────────┐
│ VotoClaro                Dashboard  Iniciativas  Partidos  Sobre  [☀/🌙] │
└──────────────────────────────────────────────────────────────┘

Desktop: Horizontal bar, sticky top
Mobile: Logo + hamburger menu → slide-down panel
```

**Style:**
```
Light: bg-white/80 backdrop-blur-sm border-b border-stone-200/60
Dark:  bg-stone-900/80 backdrop-blur-sm border-b border-stone-800/60
```

**Logo:** "VotoClaro" in Fraunces, font-bold, with a small amber accent dot after "Claro" (VotoClaro.)

**Active link:** text-amber-700, border-b-2 border-amber-500 (underline accent)

**Mobile hamburger:** Alpine.js toggle, smooth slide transition.

### Footer

```
┌──────────────────────────────────────────────────────────────┐
│ VotoClaro.pt                                                 │
│                                                              │
│ Navegacao          Informacao          Contacto               │
│ Dashboard          Sobre               email@votoclaro.pt     │
│ Iniciativas        Fonte dos dados     Twitter/X              │
│ Partidos           Politica de         GitHub                  │
│                    Privacidade                                 │
│                                                              │
│ ─────────────────────────────────────────────────────────── │
│ Dados: Parlamento.pt · Atualizado diariamente                │
│ © 2026 VotoClaro. Projeto open source de transparencia.      │
└──────────────────────────────────────────────────────────────┘
```

**Style:**
```
Light: bg-stone-50 border-t border-stone-200 text-stone-600
Dark:  bg-stone-900 border-t border-stone-800 text-stone-400
```

Three-column layout on desktop, stacked on mobile.

---

## 9. SEO & Meta Strategy

### Per-Page Meta Tags

**Dashboard:**
```html
<title>VotoClaro — Transparencia Parlamentar</title>
<meta name="description" content="Acompanhe como o Parlamento portugues vota. Iniciativas, resultados e comportamento dos partidos, de forma clara e acessivel.">
```

**Initiative Detail:**
```html
<title>{{ $initiative->title }} | VotoClaro</title>
<meta name="description" content="Estado: {{ status }}. Entrada: {{ date }}. Veja como cada partido votou nesta iniciativa.">
```

**Party Detail:**
```html
<title>{{ $party }} — Comportamento de Voto | VotoClaro</title>
<meta name="description" content="{{ $party }}: {{ favor }}% a favor, {{ contra }}% contra, {{ abstencao }}% abstencao. Alinhamento com o Governo: {{ alignment }}%.">
```

### JSON-LD Structured Data

**Dashboard:** `WebSite` schema with `SearchAction`
**Initiative Detail:** `LegislativeEvent` or `GovernmentService` schema
**Party Pages:** `Organization` schema

### Open Graph / Twitter Cards

All pages include:
```html
<meta property="og:title" content="...">
<meta property="og:description" content="...">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="/og-image-default.png">
<meta property="og:locale" content="pt_PT">
<meta name="twitter:card" content="summary_large_image">
```

Initiative detail pages generate dynamic OG descriptions with status and vote date.

---

## 10. URL Structure & Routing

| Page | URL Pattern | Route Name |
|------|-------------|------------|
| Dashboard | `/` | `dashboard` |
| Initiative listing | `/{legislature}/iniciativas` | `initiatives.index` |
| Initiative detail | `/{legislature}/iniciativas/{id}-{slug}` | `initiatives.show` |
| Party listing | `/{legislature}/partidos` | `parties.index` |
| Party detail | `/{legislature}/partidos/{party}` | `parties.show` |
| About | `/sobre` | `about` |

**Legislature parameter:** Roman numeral lowercase (e.g., `xvi`). Defaults to current legislature. Validated via route constraint.

**Slug generation:** Generated from initiative title using `str()->slug()`, stored in database or generated on-the-fly. Max 80 characters.

---

## 11. Dark Mode Implementation

**Strategy:** Class-based (`darkMode: 'class'` in Tailwind config)

**Alpine.js component (in layout):**

```javascript
// x-data on <html> or <body>
{
  dark: localStorage.getItem('theme') === 'dark' ||
        (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),

  init() {
    this.applyTheme();
  },

  toggle() {
    this.dark = !this.dark;
    localStorage.setItem('theme', this.dark ? 'dark' : 'light');
    this.applyTheme();
  },

  applyTheme() {
    document.documentElement.classList.toggle('dark', this.dark);
  }
}
```

**Initial state:** Respects system preference if no explicit choice stored. Once toggled, user preference persists.

**Toggle button:** Sun icon (light mode active) / Moon icon (dark mode active). Smooth icon swap with Alpine.js transition.

---

## 12. Accessibility Requirements

### Target: WCAG 2.1 AA

| Requirement | Implementation |
|-------------|---------------|
| Color independence | Every status color paired with distinct icon (check, X, clock) |
| Keyboard navigation | All interactive elements focusable, visible focus rings (ring-2 ring-amber-400) |
| Screen readers | Semantic HTML (nav, main, article, section), proper heading hierarchy |
| Touch targets | Minimum 44x44px on mobile |
| Contrast ratios | All text meets 4.5:1 minimum (verified in both light/dark modes) |
| Reduced motion | `@media (prefers-reduced-motion: reduce)` disables animations |
| Skip link | "Saltar para conteudo principal" hidden link at top |
| Language attribute | `<html lang="pt">` |
| Alt text | All decorative icons use `aria-hidden="true"`, functional icons have `aria-label` |

---

## 13. Performance Targets

### Lighthouse Score: > 90 (All Categories)

| Category | Target | Strategy |
|----------|--------|----------|
| Performance | > 90 | Server-rendered Blade, minimal JS (Alpine only), optimized fonts |
| Accessibility | > 90 | WCAG 2.1 AA compliance (see section 12) |
| Best Practices | > 90 | HTTPS, no console errors, proper image formats |
| SEO | > 90 | Full meta tags, structured data, semantic HTML, sitemap |

### Specific Targets

- **LCP** < 2.5s (server-rendered content, no client blocking)
- **FID** < 100ms (minimal JS, Alpine.js is lightweight)
- **CLS** < 0.1 (no layout shifts — fixed dimensions on cards, font-display: swap)
- **Font loading:** `font-display: swap` on all custom fonts, preload critical fonts
- **Alpine.js:** Loaded via `defer`, single bundle
- **Images:** WebP format, lazy loading for below-fold content

---

## 14. Blade Template Structure

```
resources/views/
├── layouts/
│   └── app.blade.php              # Main layout (nav, footer, dark mode, meta)
├── components/
│   ├── status-badge.blade.php     # Status badge (accepts: status, size)
│   ├── government-badge.blade.php # Government author badge
│   ├── tooltip.blade.php          # Parliamentary term tooltip
│   ├── metric-card.blade.php      # Dashboard metric card
│   ├── initiative-card.blade.php  # Initiative list item card
│   ├── party-card.blade.php       # Party listing card
│   ├── timeline.blade.php         # Vertical timeline
│   ├── vote-bar.blade.php         # Stacked vote summary bar
│   ├── vote-table.blade.php       # Party voting breakdown table
│   ├── sidebar-filters.blade.php  # Initiative listing filters
│   └── seo-meta.blade.php         # SEO meta tags component
├── pages/
│   ├── dashboard.blade.php
│   ├── initiatives/
│   │   ├── index.blade.php
│   │   ├── show.blade.php
│   │   └── _card.blade.php        # Partial for load-more AJAX
│   ├── parties/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   └── about.blade.php
└── partials/
    └── latest-votes.blade.php     # Dashboard latest votes section
```

---

## 15. Implementation Phases

### Phase 1: Foundation
- Tailwind theme configuration (colors, fonts, dark mode)
- Layout template (nav, footer, dark mode toggle)
- Blade component library (status-badge, government-badge, metric-card)
- SEO meta component
- Dashboard page with live data

### Phase 2: Initiative Pages
- Initiative listing with sidebar filters and load-more
- Initiative detail page with timeline and voting breakdown
- Tooltip system for parliamentary terms
- Vote summary bar and party table with split vote expansion

### Phase 3: Party Pages
- Party listing page with summary cards
- Party detail page with aggregate stats
- Government alignment section with expandable trend chart

### Phase 4: Polish
- About page content
- Mobile refinements (hamburger menu, filter overlay, table scroll hints)
- Lighthouse audit and optimization pass
- Accessibility audit (screen reader testing, keyboard navigation)
- Open Graph image generation
- Sitemap generation