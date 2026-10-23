<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiMealPlanStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One request for an AI meal plan, and what came of it.
 *
 * @property int $id
 * @property int $household_id
 * @property int|null $requested_by
 * @property AiMealPlanStatusEnum $status
 * @property array<string, mixed> $answers The form's answers, as AiMealPlanAnswersInput::toStored() writes them.
 * @property list<array<string, mixed>>|null $meals MealSuggestionData, as arrays.
 * @property string|null $error A message written for the person, when it failed.
 * @property Carbon|null $finished_at
 * @property Carbon $created_at
 */
class AiMealPlan extends Model
{
    /** Past the job's timeout, a plan still pending was lost with its worker. */
    public const int STALE_AFTER_SECONDS = 300;

    /** @var list<string> */
    protected $fillable = ['household_id', 'requested_by', 'status', 'answers', 'meals', 'error', 'finished_at'];

    protected function casts(): array
    {
        return [
            'status' => AiMealPlanStatusEnum::class,
            'answers' => 'array',
            'meals' => 'array',
            'finished_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** Still pending long after its job should have finished: its worker died, and it never will. */
    public function isStale(): bool
    {
        return $this->status === AiMealPlanStatusEnum::Pending
            && $this->created_at->lt(now()->subSeconds(self::STALE_AFTER_SECONDS));
    }

    /** Pending and not stale: a job is still writing it. */
    public function isInProgress(): bool
    {
        return $this->status === AiMealPlanStatusEnum::Pending && ! $this->isStale();
    }
}
