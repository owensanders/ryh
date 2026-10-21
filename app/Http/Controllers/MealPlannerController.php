<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Meals\AddPlannedMealAction;
use App\Actions\Meals\DeletePlannedMealAction;
use App\Actions\Meals\GenerateAiMealPlanAction;
use App\Actions\Meals\RescheduleMealAction;
use App\Actions\Meals\SuggestLibraryMealPlanAction;
use App\Actions\Meals\UpdatePlannedMealAction;
use App\Data\MealSuggestionData;
use App\Enums\CookingConfidenceEnum;
use App\Http\Requests\GenerateAiMealPlanRequest;
use App\Http\Requests\IndexMealPlannerRequest;
use App\Http\Requests\PlannedMealRequest;
use App\Http\Requests\RescheduleMealRequest;
use App\Http\Requests\SuggestLibraryMealPlanRequest;
use App\Models\PlannedMeal;
use App\Traits\ResolvesHouseholdTrait;
use App\Traits\ResolvesUserTrait;
use App\ViewModels\Meals\MealPlannerViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MealPlannerController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    public function index(IndexMealPlannerRequest $request, MealPlannerViewModel $mealPlanner): Response
    {
        $week = $request->validated('week');

        return Inertia::render('MealPlanner', $mealPlanner->toData(
            $this->household($request),
            $week !== null ? CarbonImmutable::createFromFormat('!Y-m-d', $week) ?? CarbonImmutable::now() : CarbonImmutable::now(),
        ));
    }

    public function store(PlannedMealRequest $request, AddPlannedMealAction $add): RedirectResponse
    {
        $meal = $add->handle($this->household($request), $request->toData(), $this->user($request));

        return back()->with('toast', "{$meal->name} added");
    }

    public function update(PlannedMealRequest $request, PlannedMeal $meal, UpdatePlannedMealAction $update): RedirectResponse
    {
        $updated = $update->handle($meal, $request->toData(), $this->user($request));

        return back()->with('toast', "{$updated->name} updated");
    }

    public function destroy(Request $request, PlannedMeal $meal, DeletePlannedMealAction $delete): RedirectResponse
    {
        $name = $meal->recipe->name;
        $delete->handle($meal, $this->user($request));

        return back()->with('toast', "{$name} removed");
    }

    public function reschedule(
        RescheduleMealRequest $request,
        PlannedMeal $meal,
        RescheduleMealAction $rescheduleMeal,
    ): RedirectResponse {
        $moved = $rescheduleMeal->handle($meal, CarbonImmutable::parse($request->validated('planned_on')));

        return back()->with('toast', "{$moved->name} moved");
    }

    public function generateAiPlan(GenerateAiMealPlanRequest $request, GenerateAiMealPlanAction $generate): RedirectResponse
    {
        $meals = $generate->handle(
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

    public function suggestLibraryPlan(SuggestLibraryMealPlanRequest $request, SuggestLibraryMealPlanAction $suggest): RedirectResponse
    {
        $meals = $suggest->handle(
            $this->household($request),
            $request->validated('diets', []),
            (string) $request->validated('avoid', ''),
        );

        return back()->with('libraryMeals', array_map(fn (MealSuggestionData $meal) => $meal->toArray(), $meals));
    }
}
