<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\User;

/**
 * Turns push on for one browser. An endpoint already registered to someone
 * else (a phone handed on, or a second account on it) moves to this user.
 */
class SavePushSubscriptionAction
{
    public function handle(User $user, string $endpoint, string $publicKey, string $authToken, ?string $contentEncoding): void
    {
        $user->updatePushSubscription($endpoint, $publicKey, $authToken, $contentEncoding);
    }
}
