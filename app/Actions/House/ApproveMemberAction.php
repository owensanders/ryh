<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;
use App\Models\User;

class ApproveMemberAction
{
    public function handle(User $member, Household $household): void
    {
        $household->members()->updateExistingPivot($member->id, ['pending' => false, 'pending_reason' => null]);
    }
}
