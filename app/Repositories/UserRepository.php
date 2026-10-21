<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\Input\ProfileInputData;
use App\Enums\NotificationCategoryEnum;
use App\Enums\PaletteEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Str;

class UserRepository
{
    /** Already-hashed password in. Registering means agreeing to this version of the terms. */
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

    /** A changed email needs verifying again, so `$unverifyEmail` clears its verification. */
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

    /** Already-hashed password in — hashing is the Action's job. */
    public function updatePassword(User $user, string $hashedPassword): User
    {
        $user->forceFill(['password' => $hashedPassword])->save();

        return $user->refresh();
    }

    /**
     * As updatePassword(), and signs out every "remember me" session too — for a
     * reset. Already-hashed password in — hashing is the Action's job.
     */
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

    /** Makes the household the one they were last in, with the colour they'll show in it. */
    public function moveIntoHousehold(User $user, Household $household, PaletteEnum $colour): void
    {
        $user->update([
            'last_household_id' => $household->id,
            'initials' => User::initialsFrom($user->name),
            'colour' => $colour,
        ]);
    }

    /** Their one free trial is used; a later household won't get another. */
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

    /** Adds or refreshes this browser's push subscription (keyed by endpoint). */
    public function savePushSubscription(User $user, string $endpoint, string $publicKey, string $authToken, ?string $contentEncoding): void
    {
        $user->updatePushSubscription($endpoint, $publicKey, $authToken, $contentEncoding);
    }

    /** Removes one of the user's own push subscriptions; someone else's endpoint is left alone. */
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
