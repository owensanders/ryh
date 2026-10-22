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
Eloquent; a page goes route → policy → controller → view model → Eloquent.
Each layer only talks to the one below it.

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
  render Inertia pages, redirect with toasts. Nothing else: no queries, no
  business rules.
- **Form requests** (`app/Http/Requests`): validate, and normalise first in
  `prepareForValidation()` (trim text, `''` → null, cast ids to int,
  defaults), so `$request->validated()` is already what gets stored. The
  controller passes `$request->validated()` to the action.
- **Actions** (`app/Actions/<Area>`): all business rules, one class per
  operation (`CreateChoreAction`), run by a public `handle()` method, the way
  Laravel's own Fortify and Jetstream actions work. They take models, enums,
  scalars, or a form request's validated array (`@param array<string, mixed>
  $data` naming its keys, as Fortify does), and return the model they worked
  on (or nothing),
  never a shaped array. `*Data` is for what the browser receives, so view
  models build it, not actions. The one exception is data flashed straight to
  the page (`MealSuggestionData` from the meal plan actions). Transactions and
  notifications start here.
- **View models** (`app/ViewModels/<Area>`): everything one page needs, built
  by a public `toData()` that returns its `*ScreenData` (`ChoreBoardViewModel`).
  Plain classes over the Data layer, not `spatie/laravel-view-models`, which
  is made for Blade. They only read: a write a page needs first (the budget's
  carry-forward) is an action the controller calls before rendering.
  `LayerShapeTest` checks actions have `handle()` and view models `toData()`.
- **Models** (`app/Models`): relations (including constrained ones like
  `Household::activeMembers()`), casts, query scopes, and methods that
  answer a question about the model or change it (`$household->isFull()`,
  `$user->belongsToHousehold($id)`, `$household->lockRow()`). Used directly
  by actions and view models; see "Where queries live" below. Name each
  column where you write (`'assigned_to' => $data['assigned_to']`) rather
  than passing the validated array straight to `create()`, so a new form
  field never writes a column by accident.

### Where queries live

Actions and view models use Eloquent directly, the conventional Laravel way:
`$household->chores()->create([...])`, `$chore->update([...])`. A query
that more than one place needs becomes a local query scope on the model,
written Laravel 12's way as a protected `#[Scope]` method (`Chore::forBoard()`),
and is reached through the household so it's already scoped:
`$household->chores()->forBoard()->get()`. Anything else stays inline where
it's used.

Controllers, middleware, form requests, policies, listeners and mail never
query: no `Model::where(...)`, no `$model->update(...)`, no relation methods like
`$household->members()`. Enforced by `QueryBoundaryTest`. One exception:
**route model binding**. Those models arrive ready, and reading their
attributes is fine.

**Relations are eager-loaded where the models are fetched**, for example
`->with('assignee')`. `Model::preventLazyLoading()` is on
(`AppServiceProvider`), so lazy-loading a relation across a collection, the
N+1 case, throws in local, test and e2e. In production it logs a warning
instead, so a miss never breaks a page. A single model loading its own
relation isn't flagged.

Policies and middleware that need to know about membership ask the model
(`$household->membershipOf($user)`, `$member->belongsToHousehold($id)`)
rather than writing a query. `/h/{household}` is resolved by a route binding
in `AppServiceProvider`.

## Data

- **Input**: no DTOs: actions take `$request->validated()` (see Form
  requests). Where "not sent" must differ from "cleared", check
  `array_key_exists()`.
- **Output**: `app/Data/*Data.php`, `spatie/laravel-data` classes. Their
  TypeScript types, and the enums', are generated into
  `resources/js/types/generated.ts` by `sail artisan typescript:transform`.
  Never edit it by hand: change the Data class and regenerate. CI fails if
  the committed file is out of date. Type a property with its enum, not
  `string`, so the page gets the exact values; for a fixed set with no enum,
  use `#[LiteralTypeScriptType]`. `ryh.ts` only names shapes a Data class
  declares inline, and `index.ts` holds the shared props and UI-only types.
- **Why Data and not models:** Inertia props are readable in the page source,
  so a `*Data` class is the list of what the browser may see. Never hand a
  model to a page.
- Collections leave a view model as `->values()->all()`, typed
  `array<int, X>`.

## Transactions

An action that writes more than once wraps the writes in `DB::transaction`.
Calls to outside services (Stripe, mail, AI) happen outside the transaction:
before it if a failure should stop the change, after it otherwise. Checks
that two requests could race, like the member cap or budget carry-forward,
lock the household row first with `$household->lockRow()`.

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
`ChorePolicy`, `ChoreData`,
`ChoreRequest`, `ChoreController`,
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
