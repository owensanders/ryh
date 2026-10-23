<?php

declare(strict_types=1);

namespace App\Inputs;

use App\Enums\HouseholdRoleEnum;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Dto;

/**
 * The household invite a new account signs up through: from the invite
 * link's signed query string, held in the session until they register
 * (ShowRegisterRequest, RegisterRequest).
 */
final class PendingInviteInput extends Dto
{
    /** Where the session keeps it between the two. */
    public const string SESSION_KEY = 'pendingInvite';

    public function __construct(
        public readonly int $householdId,
        public readonly HouseholdRoleEnum $role,
        /** Set when the inviter confirmed they're a Teen or Child's parent or guardian. */
        public readonly ?int $guardianId = null,
        public readonly ?CarbonImmutable $guardianConfirmedAt = null,
    ) {}
}
