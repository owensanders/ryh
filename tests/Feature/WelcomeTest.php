<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WelcomeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itRendersTheLandingPageForGuests(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Welcome')
                ->where('plan.name', 'Home')
                ->where('plan.features', fn (Collection $features) => $features->contains('Live notifications, in the app and on your phone')
                    && $features->contains('Install on iPhone and Android — no app store')));
    }

    #[Test]
    public function itRendersTheLandingPageForAuthenticatedUsers(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Welcome'));
    }
}
