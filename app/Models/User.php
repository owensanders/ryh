<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationCategoryEnum;
use App\Enums\PaletteEnum;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use NotificationChannels\WebPush\HasPushSubscriptions;
use SensitiveParameter;

/**
 * @property int $id
 * @property int|null $last_household_id
 * @property-read int|null $household_id Alias for `last_household_id` — see `householdId()` below.
 * @property string $name
 * @property string|null $initials
 * @property PaletteEnum $colour
 * @property string|null $status_line
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $terms_accepted_at
 * @property string|null $terms_version
 * @property list<string>|null $push_opt_outs NotificationCategoryEnum values this person has turned push off for.
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read HouseholdUser|null $pivot Present when loaded through a `Household::members()`/`User::households()` query.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasPushSubscriptions;
    use Notifiable;

    /** @var list<string> */
    protected $fillable = [
        'last_household_id',
        'name',
        'initials',
        'colour',
        'status_line',
        'email',
        'password',
        'trial_claimed_at',
        'terms_accepted_at',
        'terms_version',
        'push_opt_outs',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_claimed_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'colour' => PaletteEnum::class,
            'push_opt_outs' => 'array',
        ];
    }

    /**
     * The household they were last in: where /app lands them, and what pages
     * outside a household (settings) link back to. Never the household a
     * request is for — that comes from the URL (CurrentHouseholdHelper).
     *
     * @return BelongsTo<Household, $this>
     */
    public function lastHousehold(): BelongsTo
    {
        return $this->belongsTo(Household::class, 'last_household_id');
    }

    /** @return BelongsToMany<Household, $this, HouseholdUser> */
    public function households(): BelongsToMany
    {
        return $this->belongsToMany(Household::class)
            ->using(HouseholdUser::class)
            ->withPivot(['role', 'pending', 'pending_reason', 'guardian_confirmed_by', 'guardian_confirmed_at'])
            ->withTimestamps();
    }

    /** The two letters shown on someone's avatar. */
    public static function initialsFrom(string $name): string
    {
        return mb_strtoupper(mb_substr(trim($name), 0, 2));
    }

    /** Queued, unlike Laravel's default (VerifyEmailNotification). */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    /**
     * Queued, unlike Laravel's default (ResetPasswordNotification).
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification(#[SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /** Every category pushes unless this person has turned it off. */
    public function wantsPushFor(NotificationCategoryEnum $category): bool
    {
        return ! in_array($category->value, $this->push_opt_outs ?? [], true);
    }

    /**
     * Read-only alias for `last_household_id`, kept for the test suite's
     * fixtures (`$owner->household_id` is "the household the factory put them
     * in"). App code reads the request's household from
     * CurrentHouseholdHelper, never this.
     *
     * @return Attribute<int|null, never>
     */
    protected function householdId(): Attribute
    {
        return Attribute::make(get: fn () => $this->last_household_id);
    }

    /** @return HasMany<Chore, $this> */
    public function assignedChores(): HasMany
    {
        return $this->hasMany(Chore::class, 'assigned_to');
    }

    /** @return BelongsToMany<CalendarEvent, $this> */
    public function attendingEvents(): BelongsToMany
    {
        return $this->belongsToMany(CalendarEvent::class)->orderBy('id');
    }
}
