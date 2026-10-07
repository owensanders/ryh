<?php

declare(strict_types=1);

namespace App\UseCases\Settings;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\NotificationCategory;
use App\Models\User;

class UpdatePushOptOutsUseCase
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    /** @param list<NotificationCategory> $optOuts */
    public function execute(User $user, array $optOuts): User
    {
        return $this->users->setPushOptOuts($user, $optOuts);
    }
}
