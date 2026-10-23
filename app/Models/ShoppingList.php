<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaletteEnum;
use Database\Factories\ShoppingListFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $household_id
 * @property string $name
 * @property string $slug
 * @property PaletteEnum $colour
 * @property int $position
 * @property Collection<int, ShoppingItem> $items
 * @property-read int|null $items_count Set by withItemCounts().
 * @property-read int|null $remaining_items_count Set by withItemCounts().
 */
class ShoppingList extends Model
{
    /** @use HasFactory<ShoppingListFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['household_id', 'name', 'slug', 'colour', 'position'];

    protected function casts(): array
    {
        return ['colour' => PaletteEnum::class];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * Chaperoned, so each item knows its list without a query: ShoppingItemPolicy asks it for the household.
     *
     * @return HasMany<ShoppingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShoppingItem::class)->chaperone('list')->orderBy('position')->orderBy('id');
    }

    /**
     * How many items each list has, and how many are still to get, counted by
     * the database rather than loading the items.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withItemCounts(Builder $query): void
    {
        $query->withCount(['items', 'items as remaining_items_count' => fn (Builder $items) => $items->whereNull('completed_at')]);
    }
}
