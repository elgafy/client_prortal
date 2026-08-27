# AGENTS.md

# CRITICAL RULES MUST FOLLOW

## RESPONSES

- keep respnses concise and to the point - unless the user asks otherwise

## PLANNING MODE

- Always ask clarifying questions
- Never assume design, teck stack or features
- Use deep-dive sub-agents to assist with research
- Use deep-dive sub-agents to review the different aspects of your plan before presenting to the user

## CHANGE / EDIT MODE
- Never implement features yourself when possible - use sub-agents!
- Identify changes from the plan that can be implemented in parallel, and use sub-agents to implement the features efficiently
- When using sub-agents to implement features, act as a coordinator only
- Use the best model for the task - premium models for complex tasks (like coding) and mid-tire models for simpler tasks, like documentation
- After completing features (large or small), always run commands to verify feature is working and there is no code errors

## Project

- **Stack:** Laravel 13, PHP 8.4, Filament 3.2, Octane
- **Admin panel:** `/admin` (Filament + Shield roles/permissions)
- **README is stale** — claims Laravel 11 / PHP 8.2; ignore it
- **DB:** PostgreSQL in `.env` (`.env.example` defaults to sqlite — don't trust it)

## Commands

```bash
php artisan test                  # run all tests
php artisan test --filter=ExampleTest  # run single test
vendor/bin/pint                   # format check (no pint.json — uses default Laravel preset)
vendor/bin/pint --test            # lint-only, no changes
php artisan octane:start          # dev server (Octane installed)
php artisan migrate               # run migrations
php artisan db:seed               # seed database
```

- No CI workflows exist; verify locally before pushing
- `tests/Unit/UploadImageTest.php` hits live S3 + internet — skip if no AWS creds

## Architecture

**Core domain:** Restaurant reservation system with Sevenrooms external API integration.

- `Setting` model — key-value store for all site config; do **not** add columns for individual settings
- `OccasionSpecialItems` / `OccasionSpecialItemsCategory` — reservation add-ons with polymorphic order items
- `GiftCard` — gift cards managed via Filament
- `SpecialDays` — date-specific reservation deposits
- `OrderItems` — polymorphic (`itemable` morph) linking to `OccasionSpecialItems` or `SpecialDays`
- `ReservationService` → `makeReservation()` → `SevenroomsService::sevenroomsBook()` is the core booking flow

**API response pattern:**
```php
// Custom macro defined in AppServiceProvider, not response()->json()
return response()->res(success(), 'some_key', $data, 200);
```
- `success()` / `failed()` / `getLang()` are global helpers in `app/Helper/helpers.php`
- Messages come from `config/response.php` keyed by language (`en`/`ar`), read via `getLang()` (`lang` header)

**Middleware:**
- `CheckLicence` — appended globally; makes HTTP call to `gafystudio.com` on every request; blocks reservation routes if check fails

## Caching

Three cached data groups, all 7-day TTL, invalidated via Eloquent model events (`booted()`):

| Cache key              | Data source                         | Invalidated by                          |
|------------------------|-------------------------------------|------------------------------------------|
| `reservation_settings` | `Setting` (whereIn by keys)         | `Setting::saved` / `deleted`            |
| `occasion_items`       | `OccasionSpecialItemsCategory::with('items')` | `OccasionSpecialItems` / `OccasionSpecialItemsCategory` saved/deleted |
| `gift_cards`           | `GiftCard::all()`                   | `GiftCard::saved` / `deleted`           |

Sevenrooms API token cached separately as `apiToken` (23h TTL) in `SevenroomsService`.

**When modifying cache keys or invalidation logic:** check `ReservationService::getCached*()` methods and model `booted()` methods together.

## Conventions

- `.editorconfig`: 4 spaces, LF, final newline
- Filament resources/pages auto-discovered from `app/Filament/Resources/` and `app/Filament/Pages/`
- `app/Helper/helpers.php` autoloaded via `composer.json` `files` array
- API routes prefixed: `/api/settings/*`, `/api/reservations/*`, `/api/pages/*`
- `.agents/skills/laravel-specialist/` available for Laravel-specific guidance

## Gotchas

- `CheckLicence` middleware makes **external HTTP calls** on reservation routes — tests may fail without network
- `PaymentController` is empty — payment flow not yet implemented
- Model naming inconsistencies: `OccasionSpecialItems` (verbose), `OrderItems` (should be singular `OrderItem`), `payment.php` (lowercase filename)
- Sevenrooms env vars required: `SEVENROOMS_BASE_URL`, `SEVENROOMS_CLIENT_ID`, `SEVENROOMS_CLIENT_SECRET`, `SEVENROOMS_VENUE_ID`
- `config/response.php` must be updated when adding new API response keys (en + ar)
