<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaletteEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $household_id
 * @property string $name
 * @property string $icon
 * @property PaletteEnum $colour
 * @property int $position
 */
class DocumentFolder extends Model
{
    /** @var list<string> */
    protected $fillable = ['household_id', 'name', 'icon', 'colour', 'position'];

    protected function casts(): array
    {
        return ['colour' => PaletteEnum::class];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
