<?php

declare(strict_types=1);

use App\Data\PlanData;
use App\Http\Controllers\AppLandingController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChoreController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\HouseholdInviteController;
use App\Http\Controllers\IncomeSourceController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MealPlannerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Controllers\StatusController;
use App\Models\BudgetCategory;
use App\Models\CalendarEvent;
use App\Models\Chore;
use App\Models\DocumentFolder;
use App\Models\Household;
use App\Models\IncomeSource;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome', ['plan' => PlanData::current()]))->name('home');

Route::get('privacy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('terms', [LegalController::class, 'terms'])->name('terms');
Route::get('cookies', [LegalController::class, 'cookies'])->name('cookies');
Route::get('support', [LegalController::class, 'support'])->name('support');
Route::get('status', StatusController::class)->middleware('throttle:30,1')->name('status');

Route::get('household-invites/accept', [HouseholdInviteController::class, 'accept'])
    ->middleware(['auth', 'signed', 'throttle:app'])
    ->name('household-invites.accept');

// The bell: each person's own notifications, across all their households, so
// nothing to authorize beyond signing in.
Route::middleware(['auth', 'verified', 'throttle:app'])->group(function (): void {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::delete('notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroyAll');

    // Somewhere to land without a household in the URL: the installed app's
    // start page, and links from before households moved under /h/{household}.
    Route::get('app', AppLandingController::class)->name('app');
    Route::get('{path}', AppLandingController::class)
        ->where('path', '(dashboard|meals|recipes|shopping|calendar|chores|budget|documents|house|billing)(/.*)?');
});

// Every household screen is under /h/{household}: the household a request acts
// on is the one in its URL (ResolveHouseholdMiddleware checks they belong to
// it), so two tabs on two households never cross over.
// Authorization lives in app/Policies: every route below names its ability
// with ->can(), and RouteAuthorizationTest
// fails if one doesn't. A record from another household is a 404.
// throttle:app caps each person's requests; RouteThrottleTest fails if a
// write route has no limit.
Route::prefix('h/{household}')->middleware(['auth', 'verified', 'household', 'throttle:app'])->group(function (): void {
    // Reachable even once the trial has lapsed — this is the only place a
    // household without a subscription can go.
    Route::get('billing/required', [BillingController::class, 'required'])->name('billing.required')->can('view', Household::class);
    Route::post('billing/subscribe', [BillingController::class, 'subscribe'])->name('billing.subscribe')->can('manage', Household::class);
    Route::get('billing/checkout/complete', [BillingController::class, 'complete'])->name('billing.checkout.complete')->can('manage', Household::class);
    Route::post('billing/portal', [BillingController::class, 'portal'])->name('billing.portal')->can('manage', Household::class);

    Route::middleware('trial.active')->group(function (): void {
        Route::get('dashboard', DashboardController::class)->name('dashboard')->can('view', Household::class);

        Route::get('meals', [MealPlannerController::class, 'index'])->name('meals.index')->can('viewAny', PlannedMeal::class);
        Route::post('meals/ai-plan', [MealPlannerController::class, 'generateAiPlan'])->middleware('throttle:ai')->name('meals.aiPlan')->can('create', PlannedMeal::class);
        Route::post('meals/library-plan', [MealPlannerController::class, 'suggestLibraryPlan'])->name('meals.libraryPlan')->can('create', PlannedMeal::class);
        Route::post('meals', [MealPlannerController::class, 'store'])->name('meals.store')->can('create', PlannedMeal::class);
        Route::patch('meals/{meal}', [MealPlannerController::class, 'update'])->name('meals.update')->can('update', 'meal');
        Route::delete('meals/{meal}', [MealPlannerController::class, 'destroy'])->name('meals.destroy')->can('delete', 'meal');
        Route::patch('meals/{meal}/reschedule', [MealPlannerController::class, 'reschedule'])->name('meals.reschedule')->can('update', 'meal');

        Route::get('recipes', [RecipeController::class, 'index'])->name('recipes.index')->can('viewAny', Recipe::class);
        Route::post('recipes', [RecipeController::class, 'store'])->name('recipes.store')->can('create', Recipe::class);
        Route::patch('recipes/{recipe}', [RecipeController::class, 'update'])->name('recipes.update')->can('update', 'recipe');
        Route::delete('recipes/{recipe}', [RecipeController::class, 'destroy'])->name('recipes.destroy')->can('delete', 'recipe');
        Route::post('recipes/{recipe}/add-to-shopping-list', [RecipeController::class, 'addToShoppingList'])
            ->name('recipes.addToShoppingList')
            ->can('addToShoppingList', 'recipe');

        // Lists are found by slug within the household, so a stranger's slug is a 404 there.
        Route::get('shopping/{slug?}', [ShoppingListController::class, 'index'])->name('shopping.index')->can('viewAny', ShoppingList::class);
        Route::post('shopping/{slug}/items', [ShoppingListController::class, 'store'])->name('shopping.items.store')->can('create', ShoppingItem::class);
        Route::patch('shopping-items/{item}', [ShoppingListController::class, 'updateItem'])->name('shopping.items.update')->can('update', 'item');
        Route::patch('shopping-items/{item}/toggle', [ShoppingListController::class, 'toggle'])->name('shopping.items.toggle')->can('toggle', 'item');
        Route::delete('shopping-items/{item}', [ShoppingListController::class, 'destroyItem'])->name('shopping.items.destroy')->can('delete', 'item');
        Route::post('shopping-lists', [ShoppingListController::class, 'storeList'])->name('shopping.lists.store')->can('create', ShoppingList::class);
        Route::patch('shopping-lists/{list}', [ShoppingListController::class, 'updateList'])->name('shopping.lists.update')->can('update', 'list');
        Route::delete('shopping-lists/{list}', [ShoppingListController::class, 'destroyList'])->name('shopping.lists.destroy')->can('delete', 'list');

        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index')->can('viewAny', CalendarEvent::class);
        Route::post('calendar/events', [CalendarController::class, 'store'])->name('calendar.events.store')->can('create', CalendarEvent::class);
        Route::patch('calendar/events/{event}', [CalendarController::class, 'update'])->name('calendar.events.update')->can('update', 'event');
        Route::delete('calendar/events/{event}', [CalendarController::class, 'destroy'])->name('calendar.events.destroy')->can('delete', 'event');

        Route::get('chores', [ChoreController::class, 'index'])->name('chores.index')->can('viewAny', Chore::class);
        Route::post('chores', [ChoreController::class, 'store'])->name('chores.store')->can('create', Chore::class);
        Route::post('chores/notify', [ChoreController::class, 'notify'])->name('chores.notify')->can('notify', Chore::class);
        Route::patch('chores/{chore}', [ChoreController::class, 'update'])->name('chores.update')->can('update', 'chore');
        Route::patch('chores/{chore}/toggle', [ChoreController::class, 'toggle'])->name('chores.toggle')->can('toggle', 'chore');
        Route::patch('chores/{chore}/move', [ChoreController::class, 'move'])->name('chores.move')->can('update', 'chore');
        Route::delete('chores/{chore}', [ChoreController::class, 'destroy'])->name('chores.destroy')->can('delete', 'chore');

        Route::get('budget', [BudgetController::class, 'index'])->name('budget.index')->can('viewAny', BudgetCategory::class);
        Route::post('budget/set-up', [BudgetController::class, 'setUpMonth'])->name('budget.setUp')->can('create', BudgetCategory::class);
        Route::post('budget/categories', [BudgetController::class, 'storeCategory'])->name('budget.categories.store')->can('create', BudgetCategory::class);
        Route::patch('budget/categories/{category}', [BudgetController::class, 'updateCategory'])->name('budget.categories.update')->can('update', 'category');
        Route::delete('budget/categories/{category}', [BudgetController::class, 'destroyCategory'])->name('budget.categories.destroy')->can('delete', 'category');
        Route::post('budget/income', [IncomeSourceController::class, 'store'])->name('budget.income.store')->can('create', IncomeSource::class);
        Route::patch('budget/income/{income}', [IncomeSourceController::class, 'update'])->name('budget.income.update')->can('update', 'income');
        Route::delete('budget/income/{income}', [IncomeSourceController::class, 'destroy'])->name('budget.income.destroy')->can('delete', 'income');

        Route::get('documents/{folder?}', [DocumentController::class, 'index'])->name('documents.index')->can('view', [DocumentFolder::class, 'folder']);
        Route::post('document-folders', [DocumentController::class, 'storeFolder'])->name('documents.folders.store')->can('create', DocumentFolder::class);
        Route::delete('document-folders/{folder}', [DocumentController::class, 'destroyFolder'])->name('documents.folders.destroy')->can('delete', 'folder');
        Route::post('document-folders/{folder}/documents', [DocumentController::class, 'store'])->middleware('throttle:uploads')->name('documents.store')->can('addDocument', 'folder');
        Route::patch('documents/{document}/move', [DocumentController::class, 'move'])->name('documents.move')->can('update', 'document');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download')->can('view', 'document');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy')->can('delete', 'document');

        Route::get('house', [HouseController::class, 'index'])->name('house.index')->can('manage', Household::class);
        Route::post('house/invite', [HouseController::class, 'invite'])->middleware('throttle:emails')->name('house.invite')->can('manage', Household::class);
        Route::patch('house/members/{member}/role', [HouseController::class, 'updateRole'])->name('house.members.role')->can('manageMember', 'member');
        Route::patch('house/members/{member}/approve', [HouseController::class, 'approve'])->name('house.members.approve')->can('manageMember', 'member');
        Route::delete('house/members/{member}', [HouseController::class, 'destroy'])->name('house.members.destroy')->can('manageMember', 'member');
        Route::patch('house/join-code', [HouseController::class, 'toggleJoinCode'])->name('house.joinCode.toggle')->can('manage', Household::class);
        Route::patch('house/timezone', [HouseController::class, 'updateTimezone'])->name('house.timezone')->can('manage', Household::class);
        Route::post('house/join-code/regenerate', [HouseController::class, 'regenerateJoinCode'])->name('house.joinCode.regenerate')->can('manage', Household::class);
        Route::post('house/subscribe', [HouseController::class, 'subscribe'])->name('house.subscribe')->can('manage', Household::class);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
