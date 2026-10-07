<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HouseholdRole;
use App\Enums\Palette;
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

/**
 * @property int $id
 * @property int|null $current_household_id
 * @property-read int|null $household_id Alias for `current_household_id` — see `householdId()` below.
 * @property string $name
 * @property string|null $initials
 * @property Palette $colour
 * @property string|null $status_line
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $terms_accepted_at
 * @property string|null $terms_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read HouseholdUser|null $pivot Present when loaded through a `Household::members()`/`User::households()` query.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var list<string> */
    protected $fillable = [
        'current_household_id',
        'name',
        'initials',
        'colour',
        'status_line',
        'email',
        'password',
        'trial_claimed_at',
        'terms_accepted_at',
        'terms_version',
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
            'colour' => Palette::class,
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function currentHousehold(): BelongsTo
    {
        return $this->belongsTo(Household::class, 'current_household_id');
    }

    /**
     * Alias kept so "the acting user's own household" call sites read naturally.
     *
     * @return BelongsTo<Household, $this>
     */
    public function household(): BelongsTo
    {
        return $this->currentHousehold();
    }

    /** @return BelongsToMany<Household, $this, HouseholdUser> */
    public function households(): BelongsToMany
    {
        return $this->belongsToMany(Household::class)
            ->using(HouseholdUser::class)
            ->withPivot(['role', 'pending', 'pending_reason'])
            ->withTimestamps();
    }

    /**
     * The middleware chain and the Inertia shared props each ask for the
     * membership, so remember it for this instance — keyed by household, so a
     * switch mid-request still gets a fresh lookup.
     *
     * @var array{0: int, 1: HouseholdUser|null}|null
     */
    private ?array $membershipMemo = null;

    public function currentMembership(): ?HouseholdUser
    {
        if ($this->current_household_id === null) {
            return null;
        }

        if ($this->membershipMemo !== null && $this->membershipMemo[0] === $this->current_household_id) {
            return $this->membershipMemo[1];
        }

        /** @var HouseholdUser|null $membership */
        $membership = HouseholdUser::query()
            ->where('user_id', $this->id)
            ->where('household_id', $this->current_household_id)
            ->first();

        $this->membershipMemo = [$this->current_household_id, $membership];

        return $membership;
    }

    /** Reloading the user should reload its membership too. */
    public function refresh(): static
    {
        $this->membershipMemo = null;

        return parent::refresh();
    }

    public function currentRole(): ?HouseholdRole
    {
        return $this->currentMembership()?->role;
    }

    /**
     * Read-only alias for `current_household_id` — kept because many existing
     * call sites (feature-test fixtures, other tables' own `household_id`
     * scoping) read "the acting user's household" this way. Never write
     * through this; membership writes go through `households()`/`HouseholdUser`.
     *
     * @return Attribute<int|null, never>
     */
    protected function householdId(): Attribute
    {
        return Attribute::make(get: fn () => $this->current_household_id);
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
