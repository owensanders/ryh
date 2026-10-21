<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\User;

class DeletePushSubscriptionAction
{
    public function handle(User $user, string $endpoint): void
    {
        $user->pushSubscriptions()->where('endpoint', $endpoint)->delete();
    }
}
