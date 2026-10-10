<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HouseholdRoleEnum;
use App\Enums\PendingReasonEnum;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $household_id
 * @property int $user_id
 * @property HouseholdRoleEnum $role
 * @property bool $pending
 * @property PendingReasonEnum|null $pending_reason
 * @property int|null $guardian_confirmed_by Who confirmed they're a Teen or Child member's parent or guardian.
 * @property Carbon|null $guardian_confirmed_at
 */
class HouseholdUser extends Pivot
{
    protected $table = 'household_user';

    /**
     * The guardian columns for a membership with this role: who confirmed it and
     * when for a Teen or Child, cleared for an Owner or Adult.
     *
     * @return array{guardian_confirmed_by: int|null, guardian_confirmed_at: \DateTimeInterface|null}
     */
    public static function guardianAttributes(HouseholdRoleEnum $role, ?int $guardianId, ?\DateTimeInterface $confirmedAt = null): array
    {
        if (! $role->isForMinors()) {
            return ['guardian_confirmed_by' => null, 'guardian_confirmed_at' => null];
        }

        if ($guardianId === null) {
            throw new LogicException("A {$role->label()} can only join with a parent or guardian's confirmation.");
        }

        return ['guardian_confirmed_by' => $guardianId, 'guardian_confirmed_at' => $confirmedAt ?? now()];
    }

    protected function casts(): array
    {
        return [
            'role' => HouseholdRoleEnum::class,
            'pending' => 'boolean',
            'pending_reason' => PendingReasonEnum::class,
            'guardian_confirmed_at' => 'datetime',
        ];
    }
}
