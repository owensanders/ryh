<?php

declare(strict_types=1);

namespace App\UseCases\Settings;

use App\Models\User;
use App\Repositories\UserRepository;

/**
 * Turns push on for one browser. An endpoint already registered to someone
 * else (a phone handed on, or a second account on it) moves to this user.
 */
class SavePushSubscriptionUseCase
{
    public function __construct(private readonly UserRepository $users) {}

    public function execute(User $user, string $endpoint, string $publicKey, string $authToken, ?string $contentEncoding): void
    {
        $this->users->savePushSubscription($user, $endpoint, $publicKey, $authToken, $contentEncoding);
    }
}
