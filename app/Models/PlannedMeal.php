<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MealSlot;
use Database\Factories\PlannedMealFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_id
 * @property int $recipe_id
 * @property int|null $cook_id
 * @property Carbon $planned_on
 * @property MealSlot $slot
 * @property int $missing_ingredients
 * @property Recipe $recipe
 * @property User|null $cook
 */
class PlannedMeal extends Model
{
    /** @use HasFactory<PlannedMealFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['household_id', 'recipe_id', 'cook_id', 'planned_on', 'slot', 'missing_ingredients'];

    protected function casts(): array
    {
        return [
            'planned_on' => 'date',
            'slot' => MealSlot::class,
            'missing_ingredients' => 'integer',
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<Recipe, $this> */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /** @return BelongsTo<User, $this> */
    public function cook(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cook_id');
    }
}
