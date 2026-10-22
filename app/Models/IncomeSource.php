<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaletteEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\IncomeSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_id
 * @property string $label
 * @property string|null $meta
 * @property PaletteEnum $colour
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
            'colour' => PaletteEnum::class,
            'amount_pence' => 'integer',
            'is_recurring' => 'boolean',
            'month' => 'date',
        ];
    }

    /**
     * Recurring ones from months before this one: what SetUpBudgetMonthAction would bring in.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function recurringBefore(Builder $query, CarbonInterface $month): void
    {
        $query->where('is_recurring', true)->whereDate('month', '<', $month->startOfMonth()->toDateString());
    }

    /**
     * This month's, whichever day of it is given.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forMonth(Builder $query, CarbonImmutable $month): void
    {
        $query->whereDate('month', $month->startOfMonth()->toDateString());
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
