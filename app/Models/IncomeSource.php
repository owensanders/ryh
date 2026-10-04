<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Palette;
use Database\Factories\IncomeSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_id
 * @property string $label
 * @property string|null $meta
 * @property Palette $colour
 * @property int $amount_pence
 * @property bool $is_recurring
 * @property Carbon $month
 * @property int $position
 */
class IncomeSource extends Model
{
    /** @use HasFactory<IncomeSourceFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['household_id', 'label', 'meta', 'colour', 'amount_pence', 'is_recurring', 'month', 'position'];

    protected function casts(): array
    {
        return [
            'colour' => Palette::class,
            'amount_pence' => 'integer',
            'is_recurring' => 'boolean',
            'month' => 'date',
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
