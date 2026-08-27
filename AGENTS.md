# AGENTS.md

## Response Style

- Keep responses concise and to the point unless the user asks otherwise.

## Planning Mode

- Always ask clarifying questions before implementing.
- Never assume design, tech stack, or features — check `pdr.txt` first.
- Use sub-agents to research and review plans before presenting to the user.

## Implementation Mode

- Use sub-agents for implementation; act as coordinator.
- Use premium models for complex coding tasks, mid-tier for simpler work (docs, config).
- After completing any feature, run verification commands to confirm nothing is broken.

## Project Status

**Scaffolded and ready for development.** Laravel 13 + React/Inertia + SQLite is installed and building.

### Stack (installed)

- **Backend:** Laravel 13.29, PHP 8.4
- **Frontend:** React 19 + Inertia.js 3 + TypeScript + Vite 8 + Tailwind CSS 4
- **Database:** SQLite (`.env` configured)
- **Auth:** Not yet implemented (Laravel's built-in auth to be added)

### Key files

| File | Purpose |
|---|---|
| `pdr.txt` | **The authoritative project spec** — "Gafy Studio Clients Portal" |
| `DESIGN.md` | Clay.com design system reference (visual inspiration only) |
| `resources/js/app.tsx` | React/Inertia entrypoint |
| `resources/views/app.blade.php` | Inertia root Blade template |
| `resources/js/Layouts/AppLayout.tsx` | Base persistent layout |
| `resources/js/Pages/Dashboard.tsx` | Dashboard page (Inertia) |
| `app/Http/Middleware/HandleInertiaRequests.php` | Inertia middleware (auth + flash shared) |

## Planned Stack

Per `pdr.txt`:

- **Backend:** Laravel (latest stable)
- **Frontend:** React + Inertia.js (TypeScript preferred)
- **Database:** SQLite (per PRD; `.env` may differ when scaffolded)
- **Auth:** Laravel's built-in authentication
- **No:** currency conversion, payment gateways, external integrations, microservices

## Domain Model (Planned)

A simple client account ledger:

```
Client → Projects (what the client owes)
       → Payments (what the client has paid)
       → Balance = Projects - Payments
```

Core entities: `clients`, `projects`, `payments`, `comments` (polymorphic on projects/payments).

### Key Financial Rules (immutable — PRD §80)

1. Outstanding = sum of active/completed project amounts − sum of all client payments.
2. Payments may optionally be assigned to a project; unassigned payments are "account payments."
3. Unassigned payments reduce the client's overall outstanding but NOT individual project balances.
4. Cancelled projects are excluded from outstanding calculations.
5. Overpayments become client credit (display as credit, not negative balance).
6. Money uses `DECIMAL(19,4)` — never floating point.
7. No currency conversion. Different currencies must never be mathematically combined.
8. The backend is authoritative for all financial calculations — never trust the frontend.
9. Client users can only access their own records (strict server-side authorization).

## Roles

- **Administrator** — full access
- **Staff** — view clients, CRUD projects/payments, view reports, add comments
- **Client** — read-only access to own account (projects, payments, balance, statements)

## Development Phases (PRD §78)

1. Foundation — Laravel + React/Inertia + auth + layout + users/roles/settings
2. Clients — CRUD, list, detail, currency, account summary
3. Projects — CRUD, status, amounts, links, balances
4. Payments — CRUD, assignment, account payments, methods, history, balance calc
5. Client Portal — client auth, dashboard, projects, payments, balance, comments
6. Reports — account statement, PDF, Excel export
7. Hardening — auth tests, financial tests, audit log, multi-currency safeguards

## Installed Skills

The `.agents/skills/` directory contains reference material for the planned stack:

- `laravel-inertia-react` — Inertia page components, useForm, persistent layouts, shared data
- `laravel-security` — auth, authorization, Eloquent safety, CSRF/XSS prevention
- `frontend-design` — visual design guidance
- `laravel-specialist` — Laravel models, services, testing patterns

Load a skill when the task matches its domain.

## Commands

```bash
php artisan test                  # run all tests
php artisan test --filter=TestName  # run single test
php artisan migrate               # run migrations
php artisan serve                 # dev server
npm run dev                      # Vite dev server for React/Inertia
npm run build                    # production build
vendor/bin/pint                  # Laravel code style
```

## Conventions (from PRD)

- Standard Laravel architecture — no microservices, no event-driven patterns, no unnecessary abstractions.
- `ClientAccountService` should be the single source of truth for balance calculations — do not duplicate this logic across controllers, React pages, reports, and PDFs.
- Use Laravel Policies (`ClientPolicy`, `ProjectPolicy`, `PaymentPolicy`, `CommentPolicy`) for authorization.
- Frontend pages in `resources/js/Pages/` organized by domain: `Dashboard/`, `Clients/`, `Projects/`, `Payments/`, `Reports/`, `ClientPortal/`.
- Activity log tracks who created/changed financial records.

## Gotchas

- `DESIGN.md` is a Clay.com design system spec — it's visual inspiration only, not a binding spec for this project's UI.
- The PRD explicitly forbids: invoicing, accounting modules, expense tracking, project management features (tasks/Gantt/kanban), currency conversion, and external integrations (Stripe, QuickBooks, etc.).
- DB is SQLite (`.env` already configured). Migrations have been run (users, cache, jobs tables).
- Financial tests are the highest testing priority (PRD §76): basic balance, fully paid, overpaid/credit, unassigned payments, cancelled projects, multi-currency isolation, cross-client authorization.
