<?php

declare(strict_types=1);

namespace App\Inputs;

use App\Enums\HouseholdRoleEnum;
use Spatie\LaravelData\Dto;

/** An invite: InviteRequest's, or one of the setup wizard's. */
final class InviteInput extends Dto
{
    public function __construct(
        public readonly string $email,
        public readonly HouseholdRoleEnum $role,
    ) {}
}
