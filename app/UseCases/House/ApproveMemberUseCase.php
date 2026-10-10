<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Models\Household;
use App\Models\User;

class ApproveMemberUseCase
{
    public function __construct(
        private readonly MembershipRepositoryInterface $memberships,
    ) {}

    public function execute(User $member, Household $household): void
    {
        $this->memberships->approve($member, $household);
    }
}
