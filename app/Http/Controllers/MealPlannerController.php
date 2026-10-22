<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Meals\AddPlannedMealAction;
use App\Actions\Meals\DeletePlannedMealAction;
use App\Actions\Meals\RequestAiMealPlanAction;
use App\Actions\Meals\RescheduleMealAction;
use App\Actions\Meals\SuggestLibraryMealPlanAction;
use App\Actions\Meals\UpdatePlannedMealAction;
use App\Data\MealSuggestionData;
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
            $this->user($request),
            $week !== null ? CarbonImmutable::createFromFormat('!Y-m-d', $week) ?? $this->household($request)->now() : $this->household($request)->now(),
        ));
    }

    public function store(PlannedMealRequest $request, AddPlannedMealAction $add): RedirectResponse
    {
        $meal = $add->handle($this->household($request), $request->validated(), $this->user($request));

        return back()->with('toast', "{$meal->recipe->name} added");
    }

    public function update(PlannedMealRequest $request, PlannedMeal $meal, UpdatePlannedMealAction $update): RedirectResponse
    {
        $updated = $update->handle($this->household($request), $meal, $request->validated(), $this->user($request));

        return back()->with('toast', "{$updated->recipe->name} updated");
    }

    public function destroy(Request $request, PlannedMeal $meal, DeletePlannedMealAction $delete): RedirectResponse
    {
        $name = $meal->recipe->name;
        $delete->handle($this->household($request), $meal, $this->user($request));

        return back()->with('toast', "{$name} removed");
    }

    public function reschedule(
        RescheduleMealRequest $request,
        PlannedMeal $meal,
        RescheduleMealAction $rescheduleMeal,
    ): RedirectResponse {
        $moved = $rescheduleMeal->handle($meal, CarbonImmutable::parse($request->validated('planned_on')));

        return back()->with('toast', "{$moved->recipe->name} moved");
    }

    /** The plan itself is written by a queued job; the page picks it up as its aiPlan prop. */
    public function generateAiPlan(GenerateAiMealPlanRequest $request, RequestAiMealPlanAction $requestPlan): RedirectResponse
    {
        $requestPlan->handle($this->household($request), $request->validated(), $this->user($request));

        return back();
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
