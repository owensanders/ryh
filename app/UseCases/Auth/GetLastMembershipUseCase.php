<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Models\HouseholdUser;
use App\Models\User;

/** The setup screens' question: did they just join somewhere they're still waiting to be let into? */
class GetLastMembershipUseCase
{
    public function __construct(private readonly MembershipRepositoryInterface $memberships) {}

    public function execute(User $user): ?HouseholdUser
    {
        return $this->memberships->lastFor($user);
    }
}
