<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\NotificationCategoryEnum;
use App\Models\User;
use Illuminate\Support\Str;

class UserRepository implements UserRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /** @param array<string, mixed> $attributes */
    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->refresh();
    }

    /** Persists attributes already set on the model, guarded ones included. */
    public function save(User $user): User
    {
        $user->save();

        return $user->refresh();
    }

    /** Already-hashed password in — hashing is the use case's job. */
    public function setPassword(User $user, string $hashedPassword): User
    {
        // forceFill, because `password` is hashed by a cast and the reset flow also
        // rotates the remember token.
        $user->forceFill([
            'password' => $hashedPassword,
            'remember_token' => Str::random(60),
        ])->save();

        return $user->refresh();
    }

    public function markEmailAsVerified(User $user): bool
    {
        return $user->markEmailAsVerified();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function savePushSubscription(User $user, string $endpoint, string $publicKey, string $authToken, ?string $contentEncoding): void
    {
        $user->updatePushSubscription($endpoint, $publicKey, $authToken, $contentEncoding);
    }

    public function deletePushSubscription(User $user, string $endpoint): void
    {
        $user->pushSubscriptions()->where('endpoint', $endpoint)->delete();
    }

    public function deleteAllPushSubscriptions(User $user): void
    {
        $user->pushSubscriptions()->delete();
    }

    /** @param list<NotificationCategoryEnum> $optOuts */
    public function setPushOptOuts(User $user, array $optOuts): User
    {
        $user->update(['push_opt_outs' => array_map(fn (NotificationCategoryEnum $category) => $category->value, $optOuts)]);

        return $user->refresh();
    }
}
