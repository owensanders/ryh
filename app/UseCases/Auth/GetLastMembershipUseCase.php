<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Models\HouseholdUser;
use App\Models\User;
use App\Repositories\MembershipRepository;

/** The setup screens' question: did they just join somewhere they're still waiting to be let into? */
class GetLastMembershipUseCase
{
    public function __construct(private readonly MembershipRepository $memberships) {}

    public function execute(User $user): ?HouseholdUser
    {
        return $this->memberships->lastFor($user);
    }
}
