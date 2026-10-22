<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\BudgetCategory;
use App\Models\CalendarEvent;
use App\Models\Chore;
use App\Models\DocumentFolder;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Data;

/**
 * What the signed-in person may do in a household as a whole, shared with
 * every page: the nav links and the "add" controls. The screens are asked
 * about the household given, so the settings pages' nav works for the one
 * they were last in. Creating is only ever asked about the household in the
 * URL, so it's false outside one.
 */
class HouseholdAbilitiesData extends Data
{
    public function __construct(
        public bool $manageHousehold,
        public bool $viewBudget,
        public bool $viewDocuments,
        public bool $createChores,
        public bool $notifyChores,
        public bool $createShoppingLists,
        public bool $createShoppingItems,
        public bool $createCalendarEvents,
        public bool $createPlannedMeals,
        public bool $createRecipes,
    ) {}

    public static function for(User $user, Household $household): self
    {
        $gate = Gate::forUser($user);

        return new self(
            manageHousehold: $gate->allows('manage', $household),
            viewBudget: $gate->allows('viewAny', [BudgetCategory::class, $household]),
            viewDocuments: $gate->allows('viewAny', [DocumentFolder::class, $household]),
            createChores: $gate->allows('create', Chore::class),
            notifyChores: $gate->allows('notify', Chore::class),
            createShoppingLists: $gate->allows('create', ShoppingList::class),
            createShoppingItems: $gate->allows('create', ShoppingItem::class),
            createCalendarEvents: $gate->allows('create', CalendarEvent::class),
            createPlannedMeals: $gate->allows('create', PlannedMeal::class),
            createRecipes: $gate->allows('create', Recipe::class),
        );
    }
}
