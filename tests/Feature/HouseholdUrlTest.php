<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRoleEnum;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The household a request acts on is the one in its /h/{household} URL, never
 * one remembered on the user, so two tabs on two households can't cross over.
 */
class HouseholdUrlTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function twoTabsOnTwoHouseholdsEachWriteToTheirOwn(): void
    {
        [$user, $first, $second] = $this->memberOfTwo();

        // Interleaved, as two open tabs would send them.
        $this->actingAs($user)->post($this->hh('/chores', $first), ['name' => 'First bins'])->assertRedirect();
        $this->actingAs($user)->post($this->hh('/chores', $second), ['name' => 'Second bins'])->assertRedirect();
        $this->actingAs($user)->post($this->hh('/chores', $first), ['name' => 'First dishes'])->assertRedirect();

        $this->assertEqualsCanonicalizing(['First bins', 'First dishes'], Chore::where('household_id', $first->id)->pluck('name')->all());
        $this->assertSame(['Second bins'], Chore::where('household_id', $second->id)->pluck('name')->all());
    }

    #[Test]
    public function aRecordIsOnlyReachableThroughItsOwnHouseholdsUrl(): void
    {
        [$user, $first, $second] = $this->memberOfTwo();
        $chore = Chore::factory()->create(['household_id' => $first->id]);

        $this->actingAs($user)->patch($this->hh("/chores/{$chore->id}/toggle", $second))->assertNotFound();
        $this->actingAs($user)->patch($this->hh("/chores/{$chore->id}/toggle", $first))->assertRedirect();
    }

    #[Test]
    public function anotherHouseholdsUrlIsA404(): void
    {
        $user = User::factory()->create();
        $theirs = Household::factory()->create();

        $this->actingAs($user)->get($this->hh('/dashboard', $theirs))->assertNotFound();
        $this->actingAs($user)->get('/h/no-such-household/dashboard')->assertNotFound();
    }

    #[Test]
    public function aMemberWaitingForApprovalIsSentToTheWaitingScreen(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->create(['household_id' => $household->id, 'pending' => true]);

        $this->actingAs($user)->get($this->hh('/dashboard', $household))->assertRedirect(route('household.done', absolute: false));
    }

    #[Test]
    public function visitingAHouseholdMakesItWhereAppLands(): void
    {
        [$user, $first, $second] = $this->memberOfTwo();

        $this->actingAs($user)->get($this->hh('/chores', $second))->assertOk();

        $this->assertSame($second->id, $user->refresh()->last_household_id);
        $this->actingAs($user)->get('/app')->assertRedirect($this->hh('/dashboard', $second));
    }

    #[Test]
    public function oldLinksLandOnTheSameScreenKeepingTheirQuery(): void
    {
        $user = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);

        $this->actingAs($user)->get('/budget?month=2026-10')->assertRedirect($this->hh('/budget?month=2026-10'));
        $this->actingAs($user)->get('/shopping/weekly-shop')->assertRedirect($this->hh('/shopping/weekly-shop'));
    }

    #[Test]
    public function pagesOutsideAHouseholdShareTheOneTheyWereLastIn(): void
    {
        $user = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $household = $user->lastHousehold;

        $this->actingAs($user)->get('/settings/profile')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('household.slug', $household->slug)
            ->where('household.role', 'owner'));
    }

    #[Test]
    public function linksOnAHouseholdPageStayInThatHousehold(): void
    {
        [$user, $first, $second] = $this->memberOfTwo();

        $this->actingAs($user)->get($this->hh('/chores', $first));
        $this->actingAs($user)->get($this->hh('/chores', $second));

        $this->assertSame($this->hh('/calendar', $second), route('calendar.index', absolute: false));
    }

    /** @return array{User, Household, Household} */
    private function memberOfTwo(): array
    {
        $first = Household::factory()->create();
        $second = Household::factory()->create();
        $user = User::factory()->create(['household_id' => $first->id, 'role' => HouseholdRoleEnum::Owner]);
        $user->households()->attach($second->id, ['role' => HouseholdRoleEnum::Owner, 'pending' => false]);

        return [$user, $first, $second];
    }
}
