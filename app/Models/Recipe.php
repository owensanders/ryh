<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $household_id
 * @property string $name
 * @property string|null $description
 * @property string|null $duration_label
 * @property string|null $difficulty
 * @property list<string>|null $tags
 * @property list<array{name: string, quantity: string|null}>|null $ingredients
 * @property int $tint
 * @property bool $is_favourite
 */
class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'household_id', 'name', 'description', 'duration_label',
        'difficulty', 'tags', 'ingredients', 'tint', 'is_favourite',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'ingredients' => 'array',
            'tint' => 'integer',
            'is_favourite' => 'boolean',
        ];
    }

    /**
     * The library's order: favourites first, then by name.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inLibraryOrder(Builder $query): void
    {
        $query->orderByDesc('is_favourite')->orderBy('name');
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
