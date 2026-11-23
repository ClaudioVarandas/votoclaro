# 🏛 VOTOCLARO.PT

[pt]

O votoclaro.pt é uma plataforma de transparência amiga do cidadão que facilita a compreensão das votações parlamentares e da atividade legislativa.

Transforma os dados parlamentares oficiais em informação clara e acessível sobre:

- Como tramitam as leis no Parlamento
- Como votam os partidos
- Índices de aprovação do governo
- Cronograma legislativo

A plataforma é neutra, baseada em dados e concebida para ser transparente.



👉 Ideia 

O VotoClaro é uma ferramenta de clareza cívica para perceber como tramitam as leis na Assembleia da República.

Não é um comentário político.
Não é uma avaliação baseada em opiniões.
Apenas transparência estruturada.


[en]

votoclaro.pt is a citizen-friendly transparency platform that makes parliamentary voting and legislative activity easier to understand.

It transforms official parliamentary data into clear, readable insights about:

- How laws move through Parliament
- How parties vote
- Government approval rates
- Legislative timelines

The platform is neutral, data-driven, and designed for clarity.

👉 The Core Concept

VotoClaro is a civic clarity tool for understanding how laws move through the Assembleia da República.

Not political commentary.
Not opinionated scoring.
Just structured transparency.

## Specification

- See /docs/PROJECT_SPEC.md

---

## 🎯 Purpose

To make parliamentary activity understandable in under 60 seconds.

---

## 🏛 Core Features

### Dashboard – “How Parliament Works”
- Total initiatives
- % Approved
- % Rejected
- % In Progress
- Government Approval Rate
- Average days to approval

### Law Profile Page
- Title and author
- Status badge:
    - 🟢 Approved
    - 🔴 Rejected
    - 🟡 In Progress
- Entry and final vote dates
- Days to approval
- Party voting breakdown

### State Budget (Orçamento do Estado)
- The amendments proposed by each party and how every party voted them
- The articles of the Government's budget bill and their votes
- The topics of the amendments, found from their titles
- A hero on the home page with the budget's current stage: submitted, voting or closed

See /docs/STATE_BUDGET.md

### Legislatures
- Every page belongs to a legislature: the current one at the root, earlier ones under
  `/legislatura/{sigla}`

### Party Behavior Page
- Total votes participated
- % Favor
- % Contra
- % Abstention
- Alignment with Government initiatives

---

## ⚙️ Tech Stack

- Laravel
- Blade
- Alpine.js
- TailwindCSS
- MySQL or PostgreSQL

Single-application architecture.
No SPA.
No AI (V1).

---

## 📦 Data Source

Official parliamentary JSON dataset from Assembleia da República.

---

## 🚀 Roadmap

- [x] Database schema
- [x] Import command
- [x] Dashboard
- [x] Law profile page
- [x] Party page
- [x] Support for several legislatures
- [x] State Budget: amendments, the Government's articles, topics, home page hero
- [ ] State Budget: share card, amendment texts from PDF, matrix download as image
- [ ] UI polish
- [ ] more data ...

---

## 🧠 Philosophy

VotoClaro is not a political tool.
It is a clarity tool.

Transparency builds trust.
Clarity builds citizenship.

## Deployment

Releases are built by GitHub Actions and deployed with cv-forge.
A push to `main` only builds the release: deploy it from the `deploy` workflow in
`kadinho/deployments` on Gitea, since automatic deployments are off.
The deployment manifest is `cv-forge.yml`: shared files, hooks, the PHP-FPM reload, and the
health check at `/up`.

After each release goes live, the `after_activate` hooks clear the application cache, which is
stored in the database, and reload the queue workers and the scheduler.

## Operations

The production scheduler (`routes/console.php`) imports every day:

- 01:30 `ar:initiatives:import`: the initiatives and votes of the current legislature.
- 02:00 `ar:budget:import`: every State Budget of the current legislature.

A new legislature is registered with `ar:legislature:register {sigla} --initiatives-url=
--base-info-url=`, which imports its base information.

A new State Budget is registered with `ar:budget:register` and imported with
`ar:budget:import`. See /docs/STATE_BUDGET.md.
