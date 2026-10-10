<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

/** Events are shared, not owned, and Teens manage their own schedules, so Teen gets full access. */
class CalendarEventPolicy
{
    use AuthorizesHouseholdAccessTrait;

    public function viewAny(User $user): Response
    {
        return $this->as($user, HouseholdRoleEnum::cases());
    }

    public function create(User $user): Response
    {
        return $this->as($user, HouseholdRoleEnum::managersAndTeens());
    }

    public function update(User $user, CalendarEvent $event): Response
    {
        return $this->inHouseholdAs($user, $event->household_id, HouseholdRoleEnum::managersAndTeens());
    }

    public function delete(User $user, CalendarEvent $event): Response
    {
        return $this->update($user, $event);
    }
}
