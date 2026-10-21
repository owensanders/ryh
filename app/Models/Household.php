<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\HouseholdFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;

/**
 * @property int $id
 * @property string $name
 * @property string $slug The household's part of its URLs (/h/{slug}/...). Set once, never changed, so links keep working.
 * @property string|null $join_code
 * @property bool $join_code_enabled
 * @property string|null $location
 * @property int $streak_days
 * @property Carbon|null $streak_last_active_date
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $ai_meal_plan_generated_at
 * @property Carbon|null $last_chore_notification_sent_at
 * @property-read HouseholdUser|null $pivot Present when loaded through a `User::households()` query.
 * @property-read Collection<int, Subscription> $subscriptions
 */
class Household extends Model
{
    use Billable;

    /** @use HasFactory<HouseholdFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['name', 'slug', 'join_code', 'join_code_enabled', 'location', 'streak_days', 'streak_last_active_date', 'trial_ends_at', 'ai_meal_plan_generated_at', 'last_chore_notification_sent_at'];

    protected function casts(): array
    {
        return [
            'streak_days' => 'integer',
            'streak_last_active_date' => 'date',
            'join_code_enabled' => 'boolean',
            'trial_ends_at' => 'datetime',
            'ai_meal_plan_generated_at' => 'datetime',
            'last_chore_notification_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Every household needs a join code to be found by, whichever path
        // created it — the wizard, a seeder, a factory in a test.
        static::creating(function (Household $household): void {
            if (blank($household->join_code)) {
                $household->join_code = self::generateJoinCode($household->name);
            }

            if (blank($household->slug)) {
                $household->slug = self::generateSlug($household->name);
            }
        });
    }

    public static function generateJoinCode(string $householdName): string
    {
        $letters = Str::of($householdName)->upper()->replaceMatches('/[^A-Z]/', '');
        $prefix = $letters->isEmpty() ? 'HOME' : $letters->substr(0, 8)->value();

        do {
            $code = $prefix.'-'.Str::upper(Str::random(6));
        } while (static::where('join_code', $code)->exists());

        return $code;
    }

    /** Readable but not guessable, e.g. "the-parkers-x7k2". Not the join code: that one's semi-secret. */
    public static function generateSlug(string $householdName): string
    {
        $base = Str::slug(Str::limit($householdName, 30, '')) ?: 'home';

        do {
            $slug = $base.'-'.Str::lower(Str::random(4));
        } while (static::where('slug', $slug)->exists());

        return $slug;
    }

    /** @return BelongsToMany<User, $this, HouseholdUser> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(HouseholdUser::class)
            ->withPivot(['role', 'pending', 'pending_reason', 'guardian_confirmed_by', 'guardian_confirmed_at'])
            ->withTimestamps()
            ->orderBy('users.id');
    }

    /** @return HasMany<ShoppingList, $this> */
    public function shoppingLists(): HasMany
    {
        return $this->hasMany(ShoppingList::class)->orderBy('position');
    }

    /** @return HasMany<Chore, $this> */
    public function chores(): HasMany
    {
        return $this->hasMany(Chore::class);
    }

    /** @return HasMany<PlannedMeal, $this> */
    public function plannedMeals(): HasMany
    {
        return $this->hasMany(PlannedMeal::class);
    }

    /** @return HasMany<Recipe, $this> */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    /** @return HasMany<CalendarEvent, $this> */
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /** @return HasMany<BudgetCategory, $this> */
    public function budgetCategories(): HasMany
    {
        return $this->hasMany(BudgetCategory::class)->orderBy('position');
    }

    /** @return HasMany<IncomeSource, $this> */
    public function incomeSources(): HasMany
    {
        return $this->hasMany(IncomeSource::class)->orderBy('position');
    }

    /** @return HasMany<DocumentFolder, $this> */
    public function documentFolders(): HasMany
    {
        return $this->hasMany(DocumentFolder::class)->orderBy('position');
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Takes a row lock on the household until the transaction ends, so checks
     * two requests could race (the member cap, the budget carry-forward) run
     * one at a time. Call inside DB::transaction().
     */
    public function lockRow(): void
    {
        static::query()->whereKey($this->getKey())->lockForUpdate()->first();
    }

    /** True while the household's free trial hasn't ended and it isn't subscribed yet. */
    public function isOnTrial(): bool
    {
        return ! $this->subscribed('default') && $this->trial_ends_at?->isFuture() === true;
    }

    /** True once the trial has ended and the household still isn't subscribed. */
    public function needsToSubscribe(): bool
    {
        return ! $this->subscribed('default')
            && ($this->trial_ends_at === null || $this->trial_ends_at->isPast());
    }

    /**
     * True when the subscription still exists in Stripe but a payment has
     * failed (past due, unpaid, or never confirmed). The fix is updating the
     * card — starting a second subscription would double-charge once Stripe's
     * retries on the first one succeed.
     */
    public function hasPaymentIssue(): bool
    {
        $subscription = $this->subscription('default');

        return $subscription !== null
            && ($subscription->hasIncompletePayment() || $subscription->stripe_status === 'unpaid');
    }

    /** When "Notify Chores" can next be used — null once it already can. */
    public function choreRemindersAvailableAt(): ?Carbon
    {
        if ($this->last_chore_notification_sent_at === null) {
            return null;
        }

        $availableAt = $this->last_chore_notification_sent_at->addMinutes((int) config('notifications.chore_reminder_cooldown_minutes'));

        return $availableAt->isFuture() ? $availableAt : null;
    }

    /** True if the household hasn't generated an AI meal plan in the last week. */
    public function canGenerateAiMealPlan(): bool
    {
        return $this->ai_meal_plan_generated_at === null
            || $this->ai_meal_plan_generated_at->lt(now()->subWeek());
    }
}
