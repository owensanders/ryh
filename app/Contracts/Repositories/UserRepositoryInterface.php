<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Data\Input\ProfileInputData;
use App\Enums\NotificationCategoryEnum;
use App\Enums\PaletteEnum;
use App\Models\Household;
use App\Models\User;

interface UserRepositoryInterface
{
    /** Already-hashed password in. Registering means agreeing to this version of the terms. */
    public function create(string $name, string $email, string $hashedPassword, string $termsVersion): User;

    public function findByEmail(string $email): ?User;

    /** A changed email needs verifying again, so `$unverifyEmail` clears its verification. */
    public function updateProfile(User $user, ProfileInputData $profile, bool $unverifyEmail): User;

    /** Already-hashed password in — hashing is the use case's job. */
    public function updatePassword(User $user, string $hashedPassword): User;

    /** As updatePassword(), and signs out every "remember me" session too — for a reset. */
    public function setPassword(User $user, string $hashedPassword): User;

    public function setLastHousehold(User $user, ?int $householdId): void;

    /** Makes the household the one they were last in, with the colour they'll show in it. */
    public function moveIntoHousehold(User $user, Household $household, PaletteEnum $colour): void;

    /** Their one free trial is used; a later household won't get another. */
    public function claimTrial(User $user): void;

    public function markEmailAsVerified(User $user): bool;

    public function delete(User $user): void;

    /** Adds or refreshes this browser's push subscription (keyed by endpoint). */
    public function savePushSubscription(User $user, string $endpoint, string $publicKey, string $authToken, ?string $contentEncoding): void;

    /** Removes one of the user's own push subscriptions; someone else's endpoint is left alone. */
    public function deletePushSubscription(User $user, string $endpoint): void;

    public function deleteAllPushSubscriptions(User $user): void;

    /** @param list<NotificationCategoryEnum> $optOuts */
    public function setPushOptOuts(User $user, array $optOuts): User;
}
