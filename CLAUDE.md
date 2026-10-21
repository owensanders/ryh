# RYH: working conventions

Laravel 12 + Inertia 2 + Vue 3 (TypeScript), run under Sail. The README
covers setup, features, and the architecture diagrams. This file is the rules.
Where a test enforces a rule, it's named; a change that breaks the rule fails CI.

## Layers

**The household comes from the URL.** Every household screen is under
`/h/{household}` (its slug). `ResolveHouseholdMiddleware` checks the person
belongs to it (else 404), puts it in `CurrentHouseholdHelper`, and sets the
URL default so `route('chores.index')` stays in that household. Read the
household from there (controllers: `ResolvesHouseholdTrait`), never from the
user: `users.last_household_id` only says where `/app` lands. Two tabs on two
households must never cross over (`HouseholdUrlTest`). On the frontend,
`route()` (`resources/js/lib/householdRoute.ts`) fills `{household}` from the
page being shown. Anything built outside a request for that household (a
notification's link, a queued email) names the household outright.

A write goes route → policy → controller → form request → action →
repository; a page goes route → policy → controller → view model →
repository. Each layer only talks to the one below it.

- **Routes** (`routes/web.php`): middleware, plus the policy ability for every
  household route: `->can('update', 'chore')`, or
  `->can('create', Chore::class)` when there's no record yet. Household routes
  go in the `h/{household}` group.
  Enforced by `RouteAuthorizationTest`.
  Every write route is also rate limited: signed-in groups carry
  `throttle:app`, and routes that send email, upload files, call the AI or
  take a guess (passwords, join codes) add a named limiter from
  `AppServiceProvider::configureRateLimiting()`. Enforced by
  `RouteThrottleTest`.
- **Policies** (`app/Policies`): all authorization. One per model, using
  `AuthorizesHouseholdAccessTrait`: another household's record is a **404**
  (never confirm it exists), and a role that can't act is a **403**. Role sets
  come from `HouseholdRoleEnum::managers()` / `managersAndTeens()`. No role or
  ownership checks anywhere else. When a model only arrives in the request
  body, the action checks it belongs to the same household.
- **Controllers** (`app/Http/Controllers`): call actions and view models,
  render Inertia pages, redirect with toasts. Nothing else: no repositories,
  no queries, no business rules.
- **Form requests** (`app/Http/Requests`): validate, then `toData()` into a
  typed `*InputData`. Never pass `array $attributes` along.
- **Actions** (`app/Actions/<Area>`): all business rules, one class per
  operation (`CreateChoreAction`), run by a public `handle()` method, the way
  Laravel's own Fortify and Jetstream actions work. They take models, enums,
  scalars or `*InputData`, and return a `*Data` object or an
  `array<int, *Data>`, never a shaped array. Transactions and notifications
  start here.
- **View models** (`app/ViewModels/<Area>`): everything one page needs, built
  by a public `toData()` that returns its `*ScreenData` (`ChoreBoardViewModel`).
  Plain classes over the Data layer, not `spatie/laravel-view-models`, which
  is made for Blade. They only read: a write a page needs first (the budget's
  carry-forward) is an action the controller calls before rendering.
  `LayerShapeTest` checks actions have `handle()` and view models `toData()`.
- **Repositories** (`app/Repositories`): every query and write. They take
  input objects or named parameters, never attribute arrays, and map them to
  columns themselves. Concrete classes, type-hinted directly and autowired: no
  interfaces, since there's only ever one implementation (tests use the real
  database).
- **Models** (`app/Models`): relations, casts, and small helpers that don't
  query (`Household::canGenerateAiMealPlan()`). A helper that would query
  belongs in a repository.

### Repositories own every query

Actions, controllers, policies, middleware and listeners never query the
database directly. That means no `Model::where(...)`, no `$model->update(...)`,
and no relation methods like `$household->members()`. Enforced by
`RepositoryBoundaryTest`. One exception: **route model binding**. Those models
arrive ready, and reading their attributes is fine.

**Relations are eager-loaded in the repository** that fetches the models,
for example `->with('assignee')`. `Model::preventLazyLoading()` is on
(`AppServiceProvider`), so lazy-loading a relation across a collection, the
N+1 case, throws in local, test and e2e. In production it logs a warning
instead, so a miss never breaks a page. A single model loading its own
relation isn't flagged.

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
- Collections leave a repository or action as `->values()->all()`, typed
  `array<int, X>`.

## Transactions

An action that writes more than once wraps the writes in `DB::transaction`.
Calls to outside services (Stripe, mail, AI) happen outside the transaction:
before it if a failure should stop the change, after it otherwise. Checks
that two requests could race, like the member cap or budget carry-forward,
lock the household row first with `HouseholdRepository::lockForUpdate()`.

## Errors

An action says what went wrong by throwing, never with `abort()`:

- A message people should read: a `UserFacingException` subclass in
  `app/Exceptions`, with named constructors holding the wording
  (`MealPlanUnavailableException::weeklyLimitReached()`). It renders itself as
  a form error under its `errorKey()`, so controllers don't catch it.
- Not found, or not this household's: `ModelNotFoundException`, which is a 404.

These are the only messages that reach the screen. Never catch
`RuntimeException` to show its message: a database error is one too.
Enforced by `UserFacingErrorsTest`.

## Notifications

Extend `HouseholdNotification`. The bell entry is stored during the request.
The live update (`UserNotificationBroadcastEvent`, on the recipient's private
`App.Models.User.{id}` channel) and any Web Push go through the queue, so a
slow Reverb or push service can't fail a request. Use
`RecipientsHelper::except($actor, [...])` to pick who hears about a change:
everyone involved, once each, never the actor.

## Naming

A class name ends with what it is: `ChoreStatusEnum`, `CreateChoreAction`,
`ChoreRepository`, `ChorePolicy`, `ChoreData`,
`ChoreInputData`, `ChoreRequest`, `ChoreController`,
`ChoreAssignedNotification`, `QueueHeartbeatJob`, `RecordLoginStreakListener`,
`UserNotificationBroadcastEvent`, `ResolveHouseholdMiddleware`,
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
