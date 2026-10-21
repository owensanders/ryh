<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function signUpsFromOneAddressAreLimited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register')->assertStatus(302);
        }

        $this->post('/register')->assertStatus(429);
        $this->post('/forgot-password')->assertStatus(429);
    }

    #[Test]
    public function guessingJoinCodesIsLimitedPerPerson(): void
    {
        $guesser = User::factory()->create(['household_id' => null]);
        $someoneElse = User::factory()->create(['household_id' => null]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($guesser)->post('/household/join', ['code' => "NOPE-00000{$i}"])->assertSessionHasErrors('code');
        }

        $this->actingAs($guesser)->post('/household/join', ['code' => 'NOPE-000005'])->assertStatus(429);
        $this->actingAs($someoneElse)->post('/household/join', ['code' => 'NOPE-000006'])->assertSessionHasErrors('code');
    }

    #[Test]
    public function invitesAreLimitedPerHour(): void
    {
        Mail::fake();
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($owner)->post($this->hh('/house/invite'))->assertStatus(302);
        }

        $this->actingAs($owner)->post($this->hh('/house/invite'))->assertStatus(429);

        $this->travel(61)->minutes();

        $this->actingAs($owner)->post($this->hh('/house/invite'))->assertStatus(302);
    }

    #[Test]
    public function everySignedInRouteSharesAPerPersonCeiling(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);

        for ($i = 0; $i < 120; $i++) {
            $this->actingAs($owner)->get('/notifications')->assertOk();
        }

        $this->actingAs($owner)->get('/notifications')->assertStatus(429);
    }

    #[Test]
    public function anInertiaFormThatHitsALimitGoesBackWithAMessage(): void
    {
        $guesser = User::factory()->create(['household_id' => null]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($guesser)->post('/household/join', ['code' => "NOPE-00000{$i}"]);
        }

        $this->actingAs($guesser)
            ->from('/household/setup')
            ->withHeader('X-Inertia', 'true')
            ->post('/household/join', ['code' => 'NOPE-000005'])
            ->assertRedirect('/household/setup')
            ->assertSessionHas('toastError', fn (string $message) => str_starts_with($message, 'Too many attempts. Try again in '));
    }
}
