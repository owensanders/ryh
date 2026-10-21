<?php

namespace Tests;

use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // No test may reach a real external service (e.g. the paid AI API).
        // Fake the request with Http::fake() or the relevant SDK fake instead.
        Http::preventStrayRequests();
    }

    /**
     * A household screen's URL: `$this->hh('/chores')` is /h/{slug}/chores.
     * Without a household, it's the one the signed-in person was last in
     * (the factory's household, for a fixture). Call it after actingAs():
     * `$this->actingAs($owner)->post($this->hh('/chores'))`.
     */
    protected function hh(string $path, Household|int|null $household = null): string
    {
        if ($household === null) {
            $user = auth()->user();
            $household = $user instanceof User ? $user->last_household_id : null;
        }

        $household = is_int($household) ? Household::find($household) : $household;

        if (! $household instanceof Household) {
            throw new LogicException("No household to build {$path} for: sign someone in first, or pass one.");
        }

        return "/h/{$household->slug}".$path;
    }

    /** Their membership of a household: the one they were last in, unless another is given. */
    protected function membership(User $user, Household|int|null $household = null): HouseholdUser
    {
        $householdId = $household instanceof Household ? $household->id : ($household ?? $user->refresh()->last_household_id);

        return HouseholdUser::query()->where('user_id', $user->id)->where('household_id', $householdId)->firstOrFail();
    }
}
