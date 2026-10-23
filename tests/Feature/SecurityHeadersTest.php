<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function everyPageForbidsFramingAndSniffing(): void
    {
        $pages = [$this->get('/'), $this->get('/no-such-page'), $this->actingAs(User::factory()->create())->get($this->hh('/dashboard'))];

        foreach ($pages as $response) {
            $response->assertHeader('Content-Security-Policy', "frame-ancestors 'none'")
                ->assertHeader('X-Frame-Options', 'DENY')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeaderMissing('Strict-Transport-Security');
        }
    }

    #[Test]
    public function overHttpsTheBrowserIsToldToStayOnHttps(): void
    {
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
