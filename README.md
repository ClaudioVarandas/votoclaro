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
