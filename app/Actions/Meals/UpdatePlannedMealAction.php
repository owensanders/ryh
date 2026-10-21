<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Actions\Shopping\AddRecipeIngredientsToListAction;
use App\Enums\MealSlotEnum;
use App\Models\PlannedMeal;
use App\Models\User;
use App\Notifications\MealAssignedNotification;
use App\Notifications\MealEditedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;

class UpdatePlannedMealAction
{
    public function __construct(
        private readonly ResolvePlannedRecipeAction $resolveRecipe,
        private readonly AddRecipeIngredientsToListAction $addIngredients,
    ) {}

    /** @param  array<string, mixed>  $data  PlannedMealRequest's validated input; see AddPlannedMealAction. */
    public function handle(PlannedMeal $meal, array $data, User $actor): PlannedMeal
    {
        $previousCookId = $meal->cook_id;
        $household = $meal->household ?? throw new LogicException('A planned meal always belongs to a household.');

        DB::transaction(fn () => $meal->update([
            'recipe_id' => $this->resolveRecipe->handle($household, $data)->id,
            'planned_on' => CarbonImmutable::parse($data['planned_on']),
            'slot' => MealSlotEnum::from($data['slot']),
            'cook_id' => $data['cook_id'],
        ]));

        $meal->refresh()->load(['recipe', 'cook']);

        if ($data['new_recipe_shopping_list_id'] !== null) {
            $this->addIngredients->handle($household, $meal->recipe, $data['new_recipe_shopping_list_id']);
        }

        if ($meal->cook_id !== null && $meal->cook_id !== $actor->id) {
            $notification = $meal->cook_id !== $previousCookId
                ? new MealAssignedNotification($meal, $actor)
                : new MealEditedNotification($meal, $actor);

            $meal->cook?->notify($notification);
        }

        return $meal;
    }
}
