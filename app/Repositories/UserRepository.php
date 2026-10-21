<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Data\Input\ProfileInputData;
use App\Enums\NotificationCategoryEnum;
use App\Enums\PaletteEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Str;

class UserRepository implements UserRepositoryInterface
{
    public function create(string $name, string $email, string $hashedPassword, string $termsVersion): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'terms_accepted_at' => now(),
            'terms_version' => $termsVersion,
        ]);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function updateProfile(User $user, ProfileInputData $profile, bool $unverifyEmail): User
    {
        $user->fill(['name' => $profile->name, 'email' => $profile->email]);

        // Set directly rather than through fill() — `email_verified_at` is not fillable.
        if ($unverifyEmail) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user->refresh();
    }

    public function updatePassword(User $user, string $hashedPassword): User
    {
        $user->forceFill(['password' => $hashedPassword])->save();

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

    public function setLastHousehold(User $user, ?int $householdId): void
    {
        $user->forceFill(['last_household_id' => $householdId])->save();
    }

    public function moveIntoHousehold(User $user, Household $household, PaletteEnum $colour): void
    {
        $user->update([
            'last_household_id' => $household->id,
            'initials' => User::initialsFrom($user->name),
            'colour' => $colour,
        ]);
    }

    public function claimTrial(User $user): void
    {
        $user->update(['trial_claimed_at' => $user->trial_claimed_at ?? now()]);
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
