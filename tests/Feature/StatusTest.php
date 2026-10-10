<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Ai\AiConfig;
use App\Enums\ServiceState;
use App\Jobs\QueueHeartbeat;
use App\Status\PlatformProbes;
use App\UseCases\Status\GetPlatformStatusUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class StatusTest extends TestCase
{
    use RefreshDatabase;

    /** @param  array<string, ServiceState>  $overrides */
    private function probesReturning(array $overrides = []): MockInterface
    {
        return $this->mock(PlatformProbes::class, function (MockInterface $mock) use ($overrides): void {
            foreach (['database', 'cache', 'queue', 'realtime', 'ai', 'documents'] as $probe) {
                $mock->allows([$probe => $overrides[$probe] ?? ServiceState::Operational]);
            }
        });
    }

    #[Test]
    public function itShowsEveryServiceOperationalWhenAllChecksPass(): void
    {
        $this->probesReturning();

        $this->get('/status')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Status')
                ->where('status.overall', 'operational')
                ->has('status.services', 7)
                ->where('status.services.0.name', 'Website & app')
                ->where('status.services.0.state', 'operational'));
    }

    #[Test]
    public function theOverallStateIsTheWorstService(): void
    {
        $this->probesReturning(['database' => ServiceState::Down, 'ai' => ServiceState::Degraded]);

        $this->get('/status')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('status.overall', 'down')
                ->where('status.services.1.name', 'Household data')
                ->where('status.services.1.state', 'down')
                ->where('status.services.5.name', 'AI meal plans')
                ->where('status.services.5.state', 'degraded'));
    }

    #[Test]
    public function aDegradedServiceMakesTheOverallStateDegraded(): void
    {
        $this->probesReturning(['queue' => ServiceState::Degraded]);

        $this->get('/status')->assertInertia(fn (AssertableInertia $page) => $page->where('status.overall', 'degraded'));
    }

    #[Test]
    public function resultsAreCachedSoVisitorsCantHammerTheChecks(): void
    {
        $probes = new class extends PlatformProbes
        {
            public int $databaseChecks = 0;

            public function database(): ServiceState
            {
                $this->databaseChecks++;

                return ServiceState::Operational;
            }
        };
        $this->instance(PlatformProbes::class, $probes);

        $this->get('/status')->assertOk();
        $this->get('/status')->assertOk();
        $this->assertSame(1, $probes->databaseChecks);

        $this->travel(GetPlatformStatusUseCase::CACHE_SECONDS + 1)->seconds();
        $this->get('/status')->assertOk();
        $this->assertSame(2, $probes->databaseChecks);
    }

    #[Test]
    public function aFailingCheckReportsDownWithoutLeakingTheError(): void
    {
        DB::shouldReceive('select')->andThrow(new RuntimeException('SQLSTATE[HY000] secret-db-host:3306'));

        $this->assertSame(ServiceState::Down, app(PlatformProbes::class)->database());
    }

    #[Test]
    public function thePageNeverIncludesErrorDetails(): void
    {
        $this->probesReturning(['database' => ServiceState::Down]);

        $this->get('/status')->assertDontSee('SQLSTATE')->assertDontSee('secret');
    }

    #[Test]
    public function theQueueIsOperationalOnceAWorkerAnswersTheHeartbeat(): void
    {
        // The test queue is synchronous, so the heartbeat is handled immediately.
        $probes = app(PlatformProbes::class);

        $this->assertSame(ServiceState::Operational, $probes->queue());
        $this->assertNotNull(Cache::get(QueueHeartbeat::CACHE_KEY));
        $this->assertSame(ServiceState::Operational, $probes->queue());
    }

    #[Test]
    public function theQueueIsDegradedWhenNoWorkerPicksUpTheHeartbeat(): void
    {
        Queue::fake();
        $probes = app(PlatformProbes::class);

        $this->assertSame(ServiceState::Operational, $probes->queue());
        Queue::assertPushed(QueueHeartbeat::class, 1);

        $this->travel(PlatformProbes::QUEUE_GRACE_SECONDS + 1)->seconds();

        $this->assertSame(ServiceState::Degraded, $probes->queue());
        // Still only the one heartbeat in flight — a stuck worker doesn't build a backlog.
        Queue::assertPushed(QueueHeartbeat::class, 1);
    }

    #[Test]
    public function aiIsDegradedWhilePausedOrNotSetUp(): void
    {
        $probes = app(PlatformProbes::class);

        config(['ai.providers.'.config('ai.default').'.key' => null]);
        $this->assertSame(ServiceState::Degraded, $probes->ai());

        config(['ai.providers.'.config('ai.default').'.key' => 'test-key']);
        $this->assertSame(ServiceState::Operational, $probes->ai());

        AiConfig::pause();
        $this->assertSame(ServiceState::Degraded, $probes->ai());
    }

    #[Test]
    public function liveNotificationsAreDownWhenTheRealtimeServerIsUnreachable(): void
    {
        config(['broadcasting.connections.reverb.options.host' => null]);
        $this->assertSame(ServiceState::Down, app(PlatformProbes::class)->realtime());

        // Nothing listens on port 1 locally, so the connection is refused straight away.
        config(['broadcasting.connections.reverb.options.host' => '127.0.0.1', 'broadcasting.connections.reverb.options.port' => 1]);
        $this->assertSame(ServiceState::Down, app(PlatformProbes::class)->realtime());
    }

    #[Test]
    public function theHeartbeatJobRecordsWhenItRan(): void
    {
        $this->freezeTime();

        (new QueueHeartbeat)->handle();

        $this->assertSame(now()->getTimestamp(), Cache::get(QueueHeartbeat::CACHE_KEY));
    }
}
