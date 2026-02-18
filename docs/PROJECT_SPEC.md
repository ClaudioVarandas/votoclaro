VotoClaro.pt

A Citizen-Friendly Parliamentary Transparency Tool

1. Vision

VotoClaro.pt is a public transparency platform designed to help citizens understand how laws move through Parliament and how political parties vote.

The platform focuses on clarity, neutrality, and accessibility. It does not provide opinion, scoring, or political commentary. It provides structured data in a readable and understandable format.

Core principle:
Make parliamentary activity understandable in under 60 seconds.

2. Target Audience

Curious citizens

Journalists

Students

Civic educators

Researchers

The interface must be understandable without prior knowledge of parliamentary procedures.

3. Core Features (V1 Scope)
   3.1 Dashboard – “How Parliament Works”

Homepage overview with key metrics:

Total initiatives in current legislature

Percentage approved

Percentage rejected

Percentage in progress

Government Approval Rate

% approved

% rejected

Average days to approval

Purpose: Provide instant insight into parliamentary activity.

3.2 Law Profile Page

Each initiative will have a dedicated page containing:

Header

Title

Author (Government / Party / MP)

Status badge:

🟢 Approved

🔴 Rejected

🟡 In Progress

Entry date

Final vote date

Days to approval (if applicable)

Timeline (Simplified)

Entry

First vote (if exists)

Final vote

Publication (if exists)

Expandable detailed timeline (optional via Alpine.js).

Voting Breakdown

Table showing party positions:

Party	Position
PSD	Favor
PCP	Contra
IL	Abstenção

Color-coded:

Favor → Green

Contra → Red

Abstenção → Yellow

3.3 Party Behavior Page

For each political party:

Total votes participated

% Favor

% Contra

% Abstention

Alignment with Government

Alignment definition:
Percentage of Government-authored initiatives where the party voted in favor.

Purpose:
Allow citizens to understand voting patterns without ideological framing.

4. Non-Goals (V1)

The following are explicitly out of scope for version 1:

Individual MP performance tracking

Speech transcript analysis

AI or RAG integration

PDF parsing

Committee deep analytics

Predictive analytics

Political scoring or ranking systems

Keep architecture simple and maintainable.

5. Technical Architecture
   5.1 Stack

- Laravel 12 (PHP)
- Blade templates
- Alpine.js 3 for lightweight interactivity
- TailwindCSS 4 for styling
- MySQL database

No SPA architecture.
No separate frontend application.
Single Laravel app.

6. Data Model (Minimal V1 Schema)
   Table: initiatives
   Field	Type	Description
   id	string	IniId from source
   title	text	Initiative title
   author_type	string	Government / Party / MP
   status	string	approved / rejected / in_progress
   entry_date	date	Date of entry
   final_vote_date	date	Date of final vote
   days_to_approval	integer	Calculated difference
   Table: votes
   Field	Type	Description
   id	string	Vote ID from source
   initiative_id	string	Foreign key
   date	date	Vote date
   result	string	Approved / Rejected
   unanimous	boolean	If applicable
   Table: vote_positions
   Field	Type	Description
   vote_id	string	Foreign key
   party	string	Party acronym
   position	string	favor / contra / abstencao
7. Data Import Strategy
   Source

Official parliamentary JSON dataset.

Process

Create Laravel Artisan command: ImportParliament

Fetch JSON

Loop through initiatives

Extract:

Metadata

Final vote

Vote details

Parse vote HTML string into structured positions

Store normalized data

Import should be:

Idempotent

Safe to run daily

Scheduled via Laravel scheduler

8. Status Rules

Status is derived as:

If final vote result is “Aprovado” → approved

If final vote result is “Rejeitado” → rejected

If no final vote → in_progress

```
final_vote_date - entry_date
```


9. UX Principles

Large typography

Clear spacing

No bureaucratic language

Plain language explanations

Visual clarity over density

Mobile-first layout

Tooltips should explain parliamentary terms when necessary.

10. Roadmap
    Phase 1

Database schema

Import command

Dashboard page

Phase 2

Law profile page

Voting breakdown display

Phase 3

Party behavior page

Government Approval Rate block

Phase 4

UI polish

Performance optimization

Deployment

11. Long-Term Vision (Post V1)

Possible future expansions:

Voting similarity analysis

Individual MP profiles

Full debate transcript search

AI-powered natural language queries

Civic glossary section

These are optional and not part of initial release.

12. Guiding Philosophy

VotoClaro is not a political tool.
It is a clarity tool.

The goal is not to influence opinion.
The goal is to make parliamentary behavior visible and understandable.

Transparency builds trust.
Clarity builds citizenship.
