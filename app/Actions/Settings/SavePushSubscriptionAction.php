<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Inputs\PushSubscriptionInput;
use App\Models\User;

/**
 * Turns push on for one browser. An endpoint already registered to someone
 * else (a phone handed on, or a second account on it) moves to this user.
 */
class SavePushSubscriptionAction
{
    public function handle(User $user, PushSubscriptionInput $subscription): void
    {
        $user->updatePushSubscription($subscription->endpoint, $subscription->publicKey, $subscription->authToken, $subscription->contentEncoding);
    }
}
