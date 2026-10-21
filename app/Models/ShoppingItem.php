<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Builders\ShoppingItemBuilder;
use Database\Factories\ShoppingItemFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shopping_list_id
 * @property string $name
 * @property string|null $quantity
 * @property Carbon|null $completed_at
 * @property int $position
 */
#[UseEloquentBuilder(ShoppingItemBuilder::class)]
class ShoppingItem extends Model
{
    /** @use HasFactory<ShoppingItemFactory> */
    use HasFactory;

    use MassPrunable;

    /** How long a ticked-off item stays on its list before the nightly prune clears it. */
    public const int KEEP_COMPLETED_DAYS = 7;

    /** @var list<string> */
    protected $fillable = ['shopping_list_id', 'name', 'quantity', 'completed_at', 'position'];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function prunable(): ShoppingItemBuilder
    {
        return static::query()->where('completed_at', '<', now()->subDays(self::KEEP_COMPLETED_DAYS));
    }

    public function isDone(): bool
    {
        return $this->completed_at !== null;
    }

    /** @return BelongsTo<ShoppingList, $this> */
    public function list(): BelongsTo
    {
        return $this->belongsTo(ShoppingList::class, 'shopping_list_id');
    }
}
