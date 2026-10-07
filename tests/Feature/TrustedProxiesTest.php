<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private array $forwarded = [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'tunnel.example.test',
        'X-Forwarded-Port' => '443',
    ];

    #[Test]
    public function forwardedHeadersAreIgnoredByDefault(): void
    {
        config(['trustedproxy.proxies' => null]);

        $this->withHeaders($this->forwarded)->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function aTrustedProxyMakesLinksUseItsHttpsAddress(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $this->withHeaders($this->forwarded)->get('/dashboard')
            ->assertRedirect('https://tunnel.example.test/login');
    }
}
