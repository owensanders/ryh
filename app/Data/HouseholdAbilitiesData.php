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
 * What a person may do in a household as a whole, shared with every page:
 * the nav links and the "add" controls. Asked about the household given: the
 * one in the URL, or on the settings pages the one they were last in.
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
            createChores: $gate->allows('create', [Chore::class, $household]),
            notifyChores: $gate->allows('notify', [Chore::class, $household]),
            createShoppingLists: $gate->allows('create', [ShoppingList::class, $household]),
            createShoppingItems: $gate->allows('create', [ShoppingItem::class, $household]),
            createCalendarEvents: $gate->allows('create', [CalendarEvent::class, $household]),
            createPlannedMeals: $gate->allows('create', [PlannedMeal::class, $household]),
            createRecipes: $gate->allows('create', [Recipe::class, $household]),
        );
    }
}
