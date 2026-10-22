<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HouseholdRoleEnum;
use App\Enums\PendingReasonEnum;
use Carbon\CarbonImmutable;
use Database\Factories\HouseholdFactory;
use DateTimeInterface;
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
 * @property string $timezone An IANA name, e.g. Europe/London.
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
    protected $fillable = ['name', 'slug', 'join_code', 'join_code_enabled', 'location', 'timezone', 'streak_days', 'streak_last_active_date', 'trial_ends_at', 'ai_meal_plan_generated_at', 'last_chore_notification_sent_at'];

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

    /**
     * Members who have joined: no one still waiting on an invite or approval.
     *
     * @return BelongsToMany<User, $this, HouseholdUser>
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('pending', false);
    }

    /**
     * Owners who have joined: who billing emails go to.
     *
     * @return BelongsToMany<User, $this, HouseholdUser>
     */
    public function activeOwners(): BelongsToMany
    {
        return $this->activeMembers()->wherePivot('role', HouseholdRoleEnum::Owner->value);
    }

    /** Their membership here, or null if they aren't a member (pending ones included). */
    public function membershipOf(User $user): ?HouseholdUser
    {
        return HouseholdUser::query()->where('user_id', $user->id)->where('household_id', $this->id)->first();
    }

    /** True if no one but `$member` is an Owner here. */
    public function isSoleOwner(User $member): bool
    {
        return ! $this->members()
            ->wherePivot('role', HouseholdRoleEnum::Owner->value)
            ->where('users.id', '!=', $member->id)
            ->exists();
    }

    /** True once there's no room left: pending invites and join requests hold a place too. */
    public function isFull(): bool
    {
        return $this->members()->count() >= config('plans.max_members');
    }

    /** A Teen or Child needs a guardian's confirmation; see HouseholdUser::guardianAttributes(). */
    public function addMember(
        User $user,
        HouseholdRoleEnum $role,
        bool $pending = false,
        ?PendingReasonEnum $pendingReason = null,
        ?int $guardianId = null,
        ?DateTimeInterface $guardianConfirmedAt = null,
    ): void {
        $this->members()->attach($user->id, [
            'role' => $role,
            'pending' => $pending,
            'pending_reason' => $pendingReason,
            ...HouseholdUser::guardianAttributes($role, $guardianId, $guardianConfirmedAt),
        ]);
    }

    /** @return HasMany<ShoppingList, $this> */
    public function shoppingLists(): HasMany
    {
        return $this->hasMany(ShoppingList::class)->orderBy('position')->orderBy('id');
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
        return $this->hasMany(BudgetCategory::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<IncomeSource, $this> */
    public function incomeSources(): HasMany
    {
        return $this->hasMany(IncomeSource::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<DocumentFolder, $this> */
    public function documentFolders(): HasMany
    {
        return $this->hasMany(DocumentFolder::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<AiMealPlan, $this> */
    public function aiMealPlans(): HasMany
    {
        return $this->hasMany(AiMealPlan::class);
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** Bytes its documents take up, counted against `documents.quota_bytes`. */
    public function documentBytes(): int
    {
        return (int) $this->documents()->sum('size');
    }

    /**
     * Takes a row lock on the household until the transaction ends, so checks
     * two requests could race (the member cap, the budget carry-forward) run
     * one at a time, and re-reads it under that lock. Call inside
     * DB::transaction().
     */
    public function lockRow(): static
    {
        $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
        $this->setRawAttributes($locked->getAttributes(), sync: true);

        return $this;
    }

    /**
     * The household's wall-clock time right now, in the app's time zone.
     *
     * Times the household enters (calendar events) are stored as the clock
     * showed them, not converted to UTC, so this is what they compare with.
     */
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::parse(CarbonImmutable::now($this->timezone)->toDateTimeString());
    }

    /** The household's date today: it turns over at their midnight, not UTC's. */
    public function today(): CarbonImmutable
    {
        return $this->now()->startOfDay();
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
