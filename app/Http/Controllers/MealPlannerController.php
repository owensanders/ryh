<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\MealSuggestionData;
use App\Enums\CookingConfidenceEnum;
use App\Enums\KitchenEquipmentEnum;
use App\Enums\MealDietEnum;
use App\Enums\MealGoalEnum;
use App\Enums\RecipeTagEnum;
use App\Http\Requests\GenerateAiMealPlanRequest;
use App\Http\Requests\IndexMealPlannerRequest;
use App\Http\Requests\PlannedMealRequest;
use App\Http\Requests\RescheduleMealRequest;
use App\Http\Requests\SuggestLibraryMealPlanRequest;
use App\Models\PlannedMeal;
use App\Traits\ResolvesHouseholdTrait;
use App\Traits\ResolvesUserTrait;
use App\UseCases\Meals\AddPlannedMealUseCase;
use App\UseCases\Meals\DeletePlannedMealUseCase;
use App\UseCases\Meals\GenerateAiMealPlanUseCase;
use App\UseCases\Meals\GetMealPlannerUseCase;
use App\UseCases\Meals\RescheduleMealUseCase;
use App\UseCases\Meals\SuggestLibraryMealPlanUseCase;
use App\UseCases\Meals\UpdatePlannedMealUseCase;
use App\UseCases\Shopping\GetShoppingListOptionsUseCase;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MealPlannerController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    public function index(
        IndexMealPlannerRequest $request,
        GetMealPlannerUseCase $getMealPlanner,
        GetShoppingListOptionsUseCase $getShoppingLists,
    ): Response {
        $week = $request->validated('week');
        $household = $this->household($request);

        return Inertia::render('MealPlanner', [
            ...$getMealPlanner->execute(
                $household,
                $week !== null ? CarbonImmutable::createFromFormat('!Y-m-d', $week) ?? CarbonImmutable::now() : CarbonImmutable::now(),
            )->toArray(),
            'shoppingLists' => $getShoppingLists->execute($household),
            'tagOptions' => RecipeTagEnum::options(),
            'aiOptions' => [
                'diets' => MealDietEnum::values(),
                'goals' => MealGoalEnum::values(),
                'confidence' => CookingConfidenceEnum::options(),
                'equipment' => KitchenEquipmentEnum::values(),
            ],
        ]);
    }

    public function store(PlannedMealRequest $request, AddPlannedMealUseCase $add): RedirectResponse
    {
        $meal = $add->execute($this->household($request), $request->toData(), $this->user($request));

        return back()->with('toast', "{$meal->name} added");
    }

    public function update(PlannedMealRequest $request, PlannedMeal $meal, UpdatePlannedMealUseCase $update): RedirectResponse
    {
        $updated = $update->execute($meal, $request->toData(), $this->user($request));

        return back()->with('toast', "{$updated->name} updated");
    }

    public function destroy(Request $request, PlannedMeal $meal, DeletePlannedMealUseCase $delete): RedirectResponse
    {
        $name = $meal->recipe->name;
        $delete->execute($meal, $this->user($request));

        return back()->with('toast', "{$name} removed");
    }

    public function reschedule(
        RescheduleMealRequest $request,
        PlannedMeal $meal,
        RescheduleMealUseCase $rescheduleMeal,
    ): RedirectResponse {
        $moved = $rescheduleMeal->execute($meal, CarbonImmutable::parse($request->validated('planned_on')));

        return back()->with('toast', "{$moved->name} moved");
    }

    public function generateAiPlan(GenerateAiMealPlanRequest $request, GenerateAiMealPlanUseCase $generate): RedirectResponse
    {
        $meals = $generate->execute(
            $this->household($request),
            (int) $request->validated('people'),
            $request->validated('budget') !== null ? (float) $request->validated('budget') : null,
            $request->validated('diets', []),
            (string) $request->validated('avoid', ''),
            $request->validated('goals', []),
            CookingConfidenceEnum::from($request->validated('confidence')),
            $request->validated('equipment', []),
            (string) $request->validated('use_up', ''),
            (string) $request->validated('notes', ''),
        );

        return back()->with('aiMeals', array_map(fn (MealSuggestionData $meal) => $meal->toArray(), $meals));
    }

    public function suggestLibraryPlan(SuggestLibraryMealPlanRequest $request, SuggestLibraryMealPlanUseCase $suggest): RedirectResponse
    {
        $meals = $suggest->execute(
            $this->household($request),
            $request->validated('diets', []),
            (string) $request->validated('avoid', ''),
        );

        return back()->with('libraryMeals', array_map(fn (MealSuggestionData $meal) => $meal->toArray(), $meals));
    }
}
