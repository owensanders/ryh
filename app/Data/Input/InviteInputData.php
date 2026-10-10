<?php

declare(strict_types=1);

namespace App\Data\Input;

use App\Enums\HouseholdRoleEnum;

/** Someone to invite, from the House tab (InviteRequest) or the setup wizard (HouseholdSetupRequest). */
final readonly class InviteInputData
{
    public function __construct(
        public string $email,
        public HouseholdRoleEnum $role,
    ) {}
}
