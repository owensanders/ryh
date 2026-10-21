<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Models\Household;
use App\Models\User;
use App\Repositories\MembershipRepository;

class ApproveMemberUseCase
{
    public function __construct(
        private readonly MembershipRepository $memberships,
    ) {}

    public function execute(User $member, Household $household): void
    {
        $this->memberships->approve($member, $household);
    }
}
