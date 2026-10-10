<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function passwordPageIsDisplayed(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/settings/password')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('settings/Password'));
    }

    #[Test]
    public function passwordCanBeUpdated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/password');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    #[Test]
    public function correctPasswordMustBeProvidedToUpdatePassword(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect('/settings/password');
    }
}
