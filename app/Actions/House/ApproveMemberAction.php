<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;
use App\Models\User;
use App\Repositories\MembershipRepository;

class ApproveMemberAction
{
    public function __construct(
        private readonly MembershipRepository $memberships,
    ) {}

    public function handle(User $member, Household $household): void
    {
        $this->memberships->approve($member, $household);
    }
}
