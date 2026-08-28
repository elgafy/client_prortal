# AGENTS.md

## Response Style

- Keep responses concise and to the point unless the user asks otherwise.

## Planning Mode

- Always ask clarifying questions before implementing.
- Never assume design, tech stack, or features — check `pdr.txt` first.
- Use sub-agents to research and review plans before presenting to the user.

## Implementation Mode

- Use sub-agents for implementation; act as coordinator.
- After completing any feature, run verification commands to confirm nothing is broken.

## Project Status

**Fresh install — auth scaffolded via the Laravel React starter kit (Fortify).**

Done by the starter kit:

- Fortify auth: login, password reset, email verification, 2FA, passkeys
- Settings pages: profile, security (password, 2FA, passkeys), appearance
- App shell: sidebar layout, breadcrumbs, shadcn-style UI components (`resources/js/components/ui/`)
- Test suite green — required enabling `RefreshDatabase` in `tests/Pest.php`

**Phase 1 (Foundation) complete:** roles on users (`admin`/`staff`/`client`), nullable `users.client_id` FK, public registration disabled, `clients`/`currencies`/`settings` tables + seeders (6 currencies, default payment methods). Local admin: `admin@example.com`.

**Phase 2 (Clients) complete:** `ClientController` (explicit `authorize()` calls — `authorizeResource` is broken on Laravel 13), `ClientPolicy`, Store/Update requests, list w/ search + pagination, create/edit forms, show page, archive/restore. Added `ui/table.tsx` + `ui/textarea.tsx`. Note: `assertInertia` works without the `X-Inertia` header (sending it causes a 409).

**Phase 3 (Projects) complete:** `ProjectController`, `ProjectPolicy`, Store/Update requests, global list w/ search, create/edit/show pages, `ClientAccountService` (bcmath, per-currency, cancelled excluded, credit branch tested). Client show page now has summary cards + projects table. Money display via `lib/format.ts` `formatMoney()`.

**Phase 4 (Payments) complete:** `payments` table (received_from/by, note, status active|void), `PaymentController` + `PaymentPolicy` + requests (assignment same-client rule, payment currency must match project currency, method validated against settings), void action (one-way, policy-guarded), global history list, create/edit/show pages. `ClientAccountService` now aggregates active payments per currency + real project balances. Client/project show pages list payments.

**Phase 5 (Client Portal) complete:** portal invitation via password broker (`clients.invite`, admin-only, creates client-role user), `EnsureInternal`/`EnsureClient` middleware (client-role users redirect to portal, never see internal routes), portal controllers scoped to own client (cross-client → 404), portal pages (dashboard/projects/payments/balance) with `portal-layout`, polymorphic `comments` with `is_internal` flag (staff write, clients see non-internal only), comment UI on internal project/payment pages.

**Phase 6 (Reports) complete:** account statement page (internal `clients/{client}/statement` + portal `portal/statement`), PDF via barryvdh/laravel-dompdf (`statements/pdf.blade.php`), Excel via maatwebsite/excel v4 (3 sheets: Summary/Projects/Payments, `App\Exports\Statement\*`), optional payment date filter — summary always uses complete totals (PRD §32). `ClientAccountService::statement()` + `globalSummary()` (admin dashboard). Both roles can export; portal is scoped to own client.

Not started: hardening (activity log, security review).

## Stack (actual)

- **Backend:** Laravel 13 (`^13.17`), PHP `^8.3`
- **Auth:** Laravel **Fortify** (already wired — extend it, don't add Breeze/Jetstream)
- **Routing:** Laravel **Wayfinder** — routes/controllers are generated to TS in `resources/js/routes/` and `resources/js/actions/`. Regenerate with `php artisan wayfinder:generate` after changing routes/controllers. Never hand-edit generated files.
- **Frontend:** Inertia 3 + React 19 + TypeScript + Vite 8 + Tailwind CSS 4
- **UI:** Radix-based shadcn-style components pre-installed (`lucide-react` icons, `sonner` toasts, sidebar kit)
- **Database:** SQLite (`.env`)
- **Testing:** Pest 5 (`RefreshDatabase` already applied globally for Feature tests)
- **Static analysis:** Larastan (`composer types:check`)
- **Style:** Pint (PHP), ESLint + Prettier (JS/TS)

## Key files

| File        | Purpose                                                                         |
| ----------- | ------------------------------------------------------------------------------- |
| `pdr.txt`   | **The authoritative project spec** — "Gafy Studio Clients Portal" (83 sections) |
| `DESIGN.md` | **Clay design system — the binding visual theme** (applied to `app.css` tokens) |

## Domain Model

A simple client account ledger:

```
Client → Projects (what the client owes)
       → Payments (what the client has paid)
       → Balance = Projects − Payments
```

Core entities: `clients`, `projects`, `payments`, `comments` (polymorphic on projects/payments).
Supporting tables: `currencies`, `settings`, `activity_logs`. Nothing more (PRD §36).

### Key Financial Rules (immutable — PRD §80)

1. Outstanding = sum of active/completed project amounts − sum of active payments.
2. Payments may optionally be assigned to a project; unassigned payments are "account payments."
3. Unassigned payments reduce the client's overall outstanding but NOT individual project balances.
4. Cancelled projects are excluded from outstanding calculations (records preserved).
5. Overpayments become client credit (display as credit, never negative balance).
6. Money is stored as **integers** (whole currency units, no decimals) — per client decision, overriding PRD §80.6's `decimal(19,4)`. Never float; `formatMoney()` renders without fraction digits.
7. No currency conversion. Different currencies must never be mathematically combined; show per-currency breakdowns + warning (PRD §34–35).
8. The backend is authoritative for all financial calculations — never trust the frontend.
9. Client users can only access their own records (strict server-side authorization).
10. Payments are voided, not deleted (`status: active/void`) — only active payments count (PRD §51–52).

## Roles (PRD §40)

- **Administrator** — full access
- **Staff** — view clients, manage projects/payments/comments, view reports
- **Client** — read-only access to own account only

## Development Phases (PRD §78)

1. Foundation — roles on users, client↔user link, disable public registration, base layouts/navigation
2. Clients — CRUD, list/search, detail page, currency, account summary
3. Projects — CRUD, status, amounts, links, balances
4. Payments — CRUD, assignment validation, account payments, methods, voiding, history, balance calc
5. Client Portal — client auth/invitation, dashboard, projects, payments, balance, comments
6. Reports — account statement, PDF, Excel export
7. Hardening — auth tests, financial tests, activity log, multi-currency safeguards

## Commands

```bash
composer test            # pint --test + phpstan + pest (full CI-style check)
php artisan test         # pest only
php artisan test --filter=Name   # single test
composer lint            # fix PHP style (pint)
npm run lint             # fix JS/TS (eslint)
npm run types:check      # tsc --noEmit
npm run build            # production build
php artisan wayfinder:generate   # regenerate TS routes/actions after backend route changes
vendor/bin/pint          # PHP style
```

## Conventions

- Standard Laravel architecture — controllers → services/actions → Eloquent models. No microservices, no event-driven patterns, no unnecessary abstractions.
- `ClientAccountService` is the single source of truth for balance/statement calculations — never duplicate this logic in controllers, React pages, reports, or PDFs (PRD §71–72).
- Use Form Requests for validation; use Policies (`ClientPolicy`, `ProjectPolicy`, `PaymentPolicy`, `CommentPolicy`) for authorization.
- Frontend pages live in `resources/js/pages/` (lowercase, starter convention): `Dashboard/`, `Clients/`, `Projects/`, `Payments/`, `Reports/`, `ClientPortal/`.
- Page layout convention: wrap all page content in one parent div after `<Head>` — `<div className="flex h-full flex-1 flex-col gap-4 p-4">` (matches the dashboard page). Never leave page content unpadded.
- Reuse the installed `components/ui/*` primitives; don't pull in new component libraries.
- Activity log records who created/changed financial records (PRD §50).
- Currencies are a simple configurable list (USD, EUR, EGP, SAR, AED, GBP initially) — no exchange rates ever (PRD §39).

## Gotchas

- The PRD explicitly forbids: invoicing, accounting modules, expense tracking, project management features (tasks/Gantt/kanban), currency conversion, external integrations (Stripe, QuickBooks, etc.), REST APIs beyond what Inertia needs.
- **Clay theming is applied**: `app.css` maps DESIGN.md tokens (cream canvas `#fffaf0`, ink `#0a0a0a`, brand accents as `bg-brand-*` classes, 12px radius). Inter loads via the bunny font provider in `vite.config.ts`. Dark mode uses Clay's `surface-dark` (`#0a1a1a`) family. Use `font-display` utility (Inter 500, negative tracking) for hero/display headings; never bold beyond 600.
- Tests run against in-memory SQLite; `RefreshDatabase` is bound globally in `tests/Pest.php`.
- Regenerate Wayfinder with `php artisan wayfinder:generate --with-form` (matches the Vite plugin's `formVariants: true`; without the flag, `.form` route variants vanish and `types:check` fails).
- Financial tests are the highest testing priority (PRD §76): basic balance, fully paid, overpaid/credit, unassigned payments, cancelled projects, multi-currency isolation, cross-client authorization.
- PRD §70 mentions MySQL in an architecture diagram — ignore; SQLite is the chosen DB (.env + PRD header).
