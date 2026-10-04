<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // No test may reach a real external service (e.g. the paid AI API).
        // Fake the request with Http::fake() or the relevant SDK fake instead.
        Http::preventStrayRequests();
    }
}
