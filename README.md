# HouseHub

Family home organiser — dashboard, meal planner, shopping lists, chores,
shared calendar, budget and a document vault, with live in-app notifications.
Built from the HouseHub design (`HouseHub.dc.html`) as a Laravel 12 + Inertia 2
+ Vue 3 (TypeScript) + Tailwind app, running under Laravel Sail.

## Features

- **Dashboard** — daily overview across the household.
- **Meal planner & recipes** — a weekly planner with an AI-generated plan
  option (via `laravel/ai`), a recipe library, and one-tap "add ingredients to
  a shopping list".
- **Shopping lists** — multiple named lists per household.
- **Chores** — a Today/Completed board with assignment, and a "Notify
  Chores" reminder button (rate-limited per household).
- **Calendar** — shared events with per-attendee assignment, or whole-house
  events with no attendee list.
- **Budget** — income sources and spending categories.
- **Documents** — a folder-based vault for household files.
- **Live notifications** — a bell in the header shows assignment, edit and
  delete activity on chores, calendar events and meals in real time via
  Laravel Reverb (WebSockets), backed by Laravel's database notifications.
- **Households** — multiple households per login, role-based access (Owner,
  Adult, Teen, Child), invites and join codes.
- **Billing** — Stripe-backed subscription (via `laravel/cashier`) with a
  30-day free trial; a household without an active subscription or trial is
  routed to `/billing/required`.

## Running it

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Then <http://localhost:8090>. Sign in as `sarah@househub.test` / `password`
(also `james@`, `mia@`, `noah@`) — the seeded "Parkers" household, already
subscribed, with chores, meals, calendar events, budget and documents in
place.

`sail up -d` also starts a `reverb` container (`php artisan reverb:start`)
for live notifications — no separate step needed.

Ports are shifted off the defaults so they don't clash with the `~/docker`
monorepo's services if both are running: app `8090`, Vite `5174`, MySQL
`3307`, Redis `6380`, Reverb `8080`.

### Optional environment variables

The app runs locally without these, but they gate specific features:

| Var(s) | Feature |
|---|---|
| `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_PRICE_HOME_MONTHLY`, `STRIPE_PRICE_HOME_ANNUAL` | Billing/subscriptions — without these, `/billing/subscribe` is disabled |
| `ANTHROPIC_API_KEY` | AI meal plan generation (Claude Haiku 4.5; override with `ANTHROPIC_TEXT_MODEL`) |
| `AWS_*` | Document storage on S3 (defaults to the local disk otherwise) |

## Checks

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail bin phpstan analyse                # clean
./vendor/bin/sail npm run lint
./vendor/bin/sail npx vue-tsc --noEmit               # see caveat below
```

## Layout

Clean architecture, matching the conventions in `../CLAUDE.md`:

| Layer | Where | Notes |
|---|---|---|
| Use cases | `app/UseCases/**` | All business logic — one class per screen or action |
| Repositories | `app/Repositories/**` | Behind `app/Contracts/Repositories/*Interface.php`, bound in `RepositoryServiceProvider` |
| DTOs | `app/Data/**` | `spatie/laravel-data`; mirrored in `resources/js/types/househub.ts` |
| Notifications | `app/Notifications/**` | Extend `HouseholdNotification`; broadcast on `household.{id}` and persist to the database |
| Screens | `resources/js/pages/*.vue` | Wrapped in `layouts/HouseHubLayout.vue` |

Design tokens live as `--hh-*` custom properties in `resources/css/app.css`
and are surfaced as Tailwind `hh-*` colours. They key off the same `.dark`
class the starter kit's appearance switcher already toggles, so the design's
dark theme comes for free.

## Known rough edges

- `vue-tsc` reports a handful of pre-existing type errors around
  Inertia form error bags (`DeleteUser`, `LeaveHousehold`, `HouseholdSetup`,
  `Register`) where the error keys involve nested/dynamic field names.
