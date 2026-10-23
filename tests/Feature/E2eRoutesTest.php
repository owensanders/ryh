<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** The Playwright suite's setup routes (routes/e2e.php) exist only when APP_ENV=e2e. */
class E2eRoutesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function theE2eSetupRoutesDontExistOutsideE2e(): void
    {
        $user = User::factory()->create();

        $this->post('/_e2e/households')->assertNotFound();
        $this->get("/_e2e/login/{$user->id}")->assertNotFound();
        $this->assertGuest();
    }
}
