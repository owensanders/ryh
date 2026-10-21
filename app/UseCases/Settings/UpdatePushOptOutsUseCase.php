<?php

declare(strict_types=1);

namespace App\UseCases\Settings;

use App\Enums\NotificationCategoryEnum;
use App\Models\User;
use App\Repositories\UserRepository;

class UpdatePushOptOutsUseCase
{
    public function __construct(private readonly UserRepository $users) {}

    /** @param list<NotificationCategoryEnum> $optOuts */
    public function execute(User $user, array $optOuts): User
    {
        return $this->users->setPushOptOuts($user, $optOuts);
    }
}
