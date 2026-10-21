<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\User;

class ClearNotificationsAction
{
    public function handle(User $user): void
    {
        $user->notifications()->delete();
    }
}
