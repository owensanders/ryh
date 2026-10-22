<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaletteEnum;
use Database\Factories\ShoppingListFactory;
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
}
