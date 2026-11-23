# State Budget (Orçamento do Estado)

The State Budget pages show the amendments the parties proposed to each budget, the articles
of the Government's bill, and how every party voted them. They live at `/orcamento` for the
current legislature, and at `/legislatura/{sigla}/orcamento` for earlier ones.

In production since 2026-11-23, with the budgets for 2025 (XVI), 2026 (XVII) and 2027 (XVII).

## Pages

| Path | What it shows |
|---|---|
| `/orcamento` | The budgets of the legislature |
| `/orcamento/{year}` | One budget: outcome figures, how each party voted, the topics and the themes |
| `/orcamento/{year}/propostas` | The vote matrix of the amendments, filterable by party, theme and outcome, with a text search |
| `/orcamento/{year}/propostas/{proposal}` | One amendment, its votes and its text |
| `/orcamento/{year}/artigos` | The articles of the Government's bill and their votes |
| `/orcamento/{year}/artigos/{article}` | One article, its votes and the amendments that change it |
| `/orcamento/{year}/assuntos/{topic}` | The amendments of one topic |

The home page opens with a hero for the budget of the current legislature: the latest one
still open, or else the latest.

## Data sources

Two open-data files on parlamento.pt for each budget:

- `OEPropostasAlteracao{year}Or_json.txt`: the amendments and their votes.
- `OE{year}Or_json.txt`: the Government's bill, its articles and their votes.

The download links are on the parlamento.pt open-data page for the State Budget. They are
long signed URLs with `%` and `&` in them, so quote them in the shell.

## Commands

| Command | What it does |
|---|---|
| `ar:budget:register {year} --legislature= --proposals-url= --budget-url=` | Registers a budget, or updates its URLs. The legislature defaults to the current one |
| `ar:budget:import [--year=]` | Imports the amendments, rebuilds the topics, imports the articles, then clears the cache |
| `ar:budget:topics [--year=]` | Rebuilds only the topics, after a change to `config/budget_topics.php` |

Without `--year`, `ar:budget:import` and `ar:budget:topics` work on every budget of the
current legislature. A budget of an earlier legislature needs `--year`.

### Adding a new budget

Run this when the Government submits a new budget bill:

```
php artisan ar:budget:register 2028 --legislature=XVII --proposals-url='…' --budget-url='…'
php artisan ar:budget:import --year=2028
```

The amendments file appears only once the amendments period opens. Until then, register the
budget with `--budget-url` alone. Add `--proposals-url` later by running the register command
again, then import. The import needs the amendments URL.

### Schedule

`routes/console.php` runs two imports every day:

- 01:30 `ar:initiatives:import`: the initiatives and their votes. This also fills
  `votes.phase`, which tells when a budget closes.
- 02:00 `ar:budget:import`: every budget of the current legislature.

The budgets of earlier legislatures are closed and are not imported again.

## Stages

`BudgetStatsService::stage()` puts each budget in one of three stages. The home page hero
changes its badge, title and figures with it.

| Stage | When |
|---|---|
| Submitted | No amendment has been voted yet |
| Voting | Some amendments are voted and others are still pending |
| Closed | The budget bill has a vote in the phase `Votação final global`, or no amendment is pending |

The vote "na generalidade" does not close a budget. The initiative's status already says
"approved" after it, so the stage reads the phase of the vote instead.

## Topics

The topics ("Os assuntos") come from the titles of the amendments on every import.
`config/budget_topics.php` teaches the reader the budget's jargon:

- stopwords;
- words that only make a topic in a pair;
- synonyms;
- `min_proposals`: the fewest amendments a topic needs;
- `pair_share`: when a word pair becomes a topic of its own.

The topics are ranked three ways:

- by how many parties propose amendments on them;
- by how many amendments they have;
- by how divided the votes on them are.

## Caching

The figures of each budget are cached for an hour, under keys `budget.{id}.*`. The import
clears the cache when it ends. Every deploy clears it too, through the `after_activate` hook
in `cv-forge.yml`.

## Not done yet

Planned for after the first release:

- A share card for each budget, with a preview image made on the server.
- The text of the amendments that are only available as PDF.
- Download of the vote matrix as an image.
