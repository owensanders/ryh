<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Enums\NotificationCategoryEnum;
use App\Models\User;
use App\Repositories\UserRepository;

class UpdatePushOptOutsAction
{
    public function __construct(private readonly UserRepository $users) {}

    /** @param list<NotificationCategoryEnum> $optOuts */
    public function handle(User $user, array $optOuts): User
    {
        return $this->users->setPushOptOuts($user, $optOuts);
    }
}
