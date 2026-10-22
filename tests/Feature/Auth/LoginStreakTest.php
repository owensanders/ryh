<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Household;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Each person's streak: the days in a row they've signed in. */
class LoginStreakTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itStartsTheStreakOnAUsersFirstLogin(): void
    {
        Date::setTestNow(CarbonImmutable::parse('2026-08-26 10:00'));
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $user->refresh();
        $this->assertSame(1, $user->streak_days);
        $this->assertTrue($user->streak_last_active_date?->isSameDay(CarbonImmutable::parse('2026-08-26')));
    }

    #[Test]
    public function itIncrementsTheStreakOnConsecutiveDayLogins(): void
    {
        $user = User::factory()->create(['streak_days' => 5, 'streak_last_active_date' => '2026-08-25']);

        Date::setTestNow(CarbonImmutable::parse('2026-08-26 10:00'));
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertSame(6, $user->refresh()->streak_days);
    }

    #[Test]
    public function itResetsTheStreakAfterAMissedDay(): void
    {
        $user = User::factory()->create(['streak_days' => 5, 'streak_last_active_date' => '2026-08-20']);

        Date::setTestNow(CarbonImmutable::parse('2026-08-26 10:00'));
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertSame(1, $user->refresh()->streak_days);
    }

    #[Test]
    public function itDoesNotDoubleCountASecondLoginOnTheSameDay(): void
    {
        $user = User::factory()->create(['streak_days' => 5, 'streak_last_active_date' => '2026-08-26']);

        Date::setTestNow(CarbonImmutable::parse('2026-08-26 10:00'));
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertSame(5, $user->refresh()->streak_days);
    }

    #[Test]
    public function membersOfOneHouseholdKeepTheirOwnStreaks(): void
    {
        $sarah = User::factory()->create(['streak_days' => 9, 'streak_last_active_date' => '2026-08-25']);
        $james = User::factory()->create(['household_id' => $sarah->household_id, 'streak_days' => 2, 'streak_last_active_date' => '2026-08-20']);

        Date::setTestNow(CarbonImmutable::parse('2026-08-26 10:00'));
        $this->post('/login', ['email' => $sarah->email, 'password' => 'password']);

        $this->assertSame(10, $sarah->refresh()->streak_days);
        $this->assertSame(2, $james->refresh()->streak_days);
    }

    #[Test]
    public function theDayTurnsOverAtTheirHouseholdsMidnight(): void
    {
        // 23:30 UTC on the 25th is already the 26th in Auckland.
        $user = User::factory()->create(['streak_days' => 3, 'streak_last_active_date' => '2026-08-25']);
        Household::where('id', $user->household_id)->update(['timezone' => 'Pacific/Auckland']);

        Date::setTestNow(CarbonImmutable::parse('2026-08-25 23:30', 'UTC'));
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $user->refresh();
        $this->assertSame(4, $user->streak_days);
        $this->assertSame('2026-08-26', $user->streak_last_active_date?->toDateString());
    }

    #[Test]
    public function theDashboardShowsTheViewersStreakAndZeroOnceItsLapsed(): void
    {
        Date::setTestNow(CarbonImmutable::parse('2026-08-26 10:00'));
        $kept = User::factory()->create(['streak_days' => 7, 'streak_last_active_date' => '2026-08-25']);
        $lapsed = User::factory()->create(['household_id' => $kept->household_id, 'streak_days' => 30, 'streak_last_active_date' => '2026-08-20']);

        $this->actingAs($kept)->get($this->hh('/dashboard'))->assertInertia(fn ($page) => $page->where('dashboard.streakDays', 7));
        $this->actingAs($lapsed)->get($this->hh('/dashboard'))->assertInertia(fn ($page) => $page->where('dashboard.streakDays', 0));
    }
}
