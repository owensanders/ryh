# RYH: working conventions

Laravel 12 + Inertia 2 + Vue 3 (TypeScript), run under Sail. The README
covers setup, features, and the architecture diagrams. This file is the rules.
Where a test enforces a rule, it's named; a change that breaks the rule fails CI.

## Layers

A request goes route → policy → controller → form request → use case →
repository. Each layer only talks to the one below it.

- **Routes** (`routes/web.php`): middleware, plus the policy ability for every
  household route: `->can('update', 'chore')`, or
  `->can('create', Chore::class)` when there's no record yet.
  Enforced by `RouteAuthorizationTest`.
- **Policies** (`app/Policies`): all authorization. One per model, using
  `AuthorizesHouseholdAccessTrait`: another household's record is a **404**
  (never confirm it exists), and a role that can't act is a **403**. Role sets
  come from `HouseholdRoleEnum::managers()` / `managersAndTeens()`. No role or
  ownership checks anywhere else. When a model only arrives in the request
  body, the use case checks it belongs to the same household.
- **Controllers** (`app/Http/Controllers`): call use cases, render Inertia
  pages, redirect with toasts. Nothing else: no repositories, no queries, no
  business rules.
- **Form requests** (`app/Http/Requests`): validate, then `toData()` into a
  typed `*InputData`. Never pass `array $attributes` along.
- **Use cases** (`app/UseCases/<Area>`): all business rules, one class per
  screen or action, with an `execute()` method. They take models, enums,
  scalars or `*InputData`, and return a `*Data` object or an
  `array<int, *Data>`, never a shaped array. Transactions and notifications
  start here.
- **Repositories** (`app/Repositories`, interfaces in
  `app/Contracts/Repositories`, bound in `RepositoryServiceProvider`): every
  query and write. They take input objects or named parameters, never
  attribute arrays, and map them to columns themselves.
- **Models** (`app/Models`): relations, casts, and small helpers that don't
  query (`Household::canGenerateAiMealPlan()`). A helper that would query
  belongs in a repository.

### Repositories own every query

Use cases, controllers, policies, middleware and listeners never query the
database directly. That means no `Model::where(...)`, no `$model->update(...)`,
and no relation methods like `$household->members()`. Enforced by
`RepositoryBoundaryTest`. Two exceptions:

- **Route model binding**: those models arrive ready, and reading their
  attributes is fine.
- **Lazy-loaded relation properties** (`$chore->assignee`) are tolerated for
  now. Prefer eager-loading in the repository.

Policies and middleware may *read* through repositories
(`MembershipRepository` is the usual one).

## Data

- **Input**: `app/Data/Input/*InputData.php`, plain `final readonly`
  classes built by a form request's `toData()`. Use Spatie's `Optional` for a
  field where "not sent" must differ from "cleared" (see
  `RecipeDetailsInputData`).
- **Output**: `app/Data/*Data.php`, `spatie/laravel-data` classes. Anything a
  page receives is mirrored in `resources/js/types/ryh.ts` (or `index.ts`).
  Change both together.
- Collections leave a repository or use case as `->values()->all()`, typed
  `array<int, X>`.

## Transactions

A use case that writes more than once wraps the writes in `DB::transaction`.
Calls to outside services (Stripe, mail, AI) happen outside the transaction:
before it if a failure should stop the change, after it otherwise. Checks
that two requests could race, like the member cap or budget carry-forward,
lock the household row first with `HouseholdRepositoryInterface::lockForUpdate()`.

## Notifications

Extend `HouseholdNotification`. The bell entry is stored during the request.
The live update (`UserNotificationBroadcastEvent`, on the recipient's private
`App.Models.User.{id}` channel) and any Web Push go through the queue, so a
slow Reverb or push service can't fail a request. Use
`RecipientsHelper::except($actor, [...])` to pick who hears about a change:
everyone involved, once each, never the actor.

## Naming

A class name ends with what it is: `ChoreStatusEnum`, `CreateChoreUseCase`,
`ChoreRepository`, `ChoreRepositoryInterface`, `ChorePolicy`, `ChoreData`,
`ChoreInputData`, `ChoreRequest`, `ChoreController`,
`ChoreAssignedNotification`, `QueueHeartbeatJob`, `RecordLoginStreakListener`,
`UserNotificationBroadcastEvent`, `EnsureHasHouseholdMiddleware`,
`MoneyHelper`, `PlatformProbesService`, `AuthorizesHouseholdAccessTrait`.
**Models are the one exception.** Their class names are stored in the database
(`notifications.notifiable_type`) and used in channel names. Enforced by
`NamingConventionsTest`, which also fails when a new folder has no naming rule.

Every class constant declares its type (`private const int LIMIT = 20;`).
Enforced by `TypedConstantsTest`.

## Checks

```bash
./vendor/bin/sail artisan test           # includes the architecture tests above
./vendor/bin/sail bin phpstan analyse    # level 8, clean
./vendor/bin/sail bin pint
./vendor/bin/sail npm run lint
./vendor/bin/sail npx vue-tsc --noEmit
npm run e2e                              # Playwright, with Sail up
```

## Keep in step

- The README's architecture diagrams are Mermaid. Update them when the shape
  of the system changes (a new service, queue or layer).
- Launch and operations items go in `docs/go-live.md`.
