<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Enums\NotificationCategory;
use App\Models\User;

interface UserRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): User;

    public function findByEmail(string $email): ?User;

    /** @param array<string, mixed> $attributes */
    public function update(User $user, array $attributes): User;

    /** Persists attributes already set on the model, guarded ones included. */
    public function save(User $user): User;

    /** Already-hashed password in — hashing is the use case's job. */
    public function setPassword(User $user, string $hashedPassword): User;

    public function markEmailAsVerified(User $user): bool;

    public function delete(User $user): void;

    /** Adds or refreshes this browser's push subscription (keyed by endpoint). */
    public function savePushSubscription(User $user, string $endpoint, string $publicKey, string $authToken, ?string $contentEncoding): void;

    /** Removes one of the user's own push subscriptions; someone else's endpoint is left alone. */
    public function deletePushSubscription(User $user, string $endpoint): void;

    public function deleteAllPushSubscriptions(User $user): void;

    /** @param list<NotificationCategory> $optOuts */
    public function setPushOptOuts(User $user, array $optOuts): User;
}
