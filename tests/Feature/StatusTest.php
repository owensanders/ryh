<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Ai\AiConfigService;
use App\Enums\ServiceStateEnum;
use App\Jobs\QueueHeartbeatJob;
use App\Status\PlatformProbesService;
use App\ViewModels\Status\PlatformStatusViewModel;
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

    /** @param  array<string, ServiceStateEnum>  $overrides */
    private function probesReturning(array $overrides = []): MockInterface
    {
        return $this->mock(PlatformProbesService::class, function (MockInterface $mock) use ($overrides): void {
            foreach (['database', 'cache', 'queue', 'realtime', 'ai', 'documents'] as $probe) {
                $mock->allows([$probe => $overrides[$probe] ?? ServiceStateEnum::Operational]);
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
        $this->probesReturning(['database' => ServiceStateEnum::Down, 'ai' => ServiceStateEnum::Degraded]);

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
        $this->probesReturning(['queue' => ServiceStateEnum::Degraded]);

        $this->get('/status')->assertInertia(fn (AssertableInertia $page) => $page->where('status.overall', 'degraded'));
    }

    #[Test]
    public function resultsAreCachedSoVisitorsCantHammerTheChecks(): void
    {
        $probes = new class extends PlatformProbesService {
            public int $databaseChecks = 0;

            public function database(): ServiceStateEnum
            {
                $this->databaseChecks++;

                return ServiceStateEnum::Operational;
            }
        };
        $this->instance(PlatformProbesService::class, $probes);

        $this->get('/status')->assertOk();
        $this->get('/status')->assertOk();
        $this->assertSame(1, $probes->databaseChecks);

        $this->travel(PlatformStatusViewModel::CACHE_SECONDS + 1)->seconds();
        $this->get('/status')->assertOk();
        $this->assertSame(2, $probes->databaseChecks);
    }

    #[Test]
    public function aFailingCheckReportsDownWithoutLeakingTheError(): void
    {
        DB::shouldReceive('select')->andThrow(new RuntimeException('SQLSTATE[HY000] secret-db-host:3306'));

        $this->assertSame(ServiceStateEnum::Down, app(PlatformProbesService::class)->database());
    }

    #[Test]
    public function thePageNeverIncludesErrorDetails(): void
    {
        $this->probesReturning(['database' => ServiceStateEnum::Down]);

        $this->get('/status')->assertDontSee('SQLSTATE')->assertDontSee('secret');
    }

    #[Test]
    public function theQueueIsOperationalOnceAWorkerAnswersTheHeartbeat(): void
    {
        // The test queue is synchronous, so the heartbeat is handled immediately.
        $probes = app(PlatformProbesService::class);

        $this->assertSame(ServiceStateEnum::Operational, $probes->queue());
        $this->assertNotNull(Cache::get(QueueHeartbeatJob::CACHE_KEY));
        $this->assertSame(ServiceStateEnum::Operational, $probes->queue());
    }

    #[Test]
    public function theQueueIsDegradedWhenNoWorkerPicksUpTheHeartbeat(): void
    {
        Queue::fake();
        $probes = app(PlatformProbesService::class);

        $this->assertSame(ServiceStateEnum::Operational, $probes->queue());
        Queue::assertPushed(QueueHeartbeatJob::class, 1);

        $this->travel(PlatformProbesService::QUEUE_GRACE_SECONDS + 1)->seconds();

        $this->assertSame(ServiceStateEnum::Degraded, $probes->queue());
        // Still only the one heartbeat in flight — a stuck worker doesn't build a backlog.
        Queue::assertPushed(QueueHeartbeatJob::class, 1);
    }

    #[Test]
    public function aiIsDegradedWhilePausedOrNotSetUp(): void
    {
        $probes = app(PlatformProbesService::class);

        config(['ai.providers.'.config('ai.default').'.key' => null]);
        $this->assertSame(ServiceStateEnum::Degraded, $probes->ai());

        config(['ai.providers.'.config('ai.default').'.key' => 'test-key']);
        $this->assertSame(ServiceStateEnum::Operational, $probes->ai());

        AiConfigService::pause();
        $this->assertSame(ServiceStateEnum::Degraded, $probes->ai());
    }

    #[Test]
    public function liveNotificationsAreDownWhenTheRealtimeServerIsUnreachable(): void
    {
        config(['broadcasting.connections.reverb.options.host' => null]);
        $this->assertSame(ServiceStateEnum::Down, app(PlatformProbesService::class)->realtime());

        // Nothing listens on port 1 locally, so the connection is refused straight away.
        config(['broadcasting.connections.reverb.options.host' => '127.0.0.1', 'broadcasting.connections.reverb.options.port' => 1]);
        $this->assertSame(ServiceStateEnum::Down, app(PlatformProbesService::class)->realtime());
    }

    #[Test]
    public function theHeartbeatJobRecordsWhenItRan(): void
    {
        $this->freezeTime();

        (new QueueHeartbeatJob())->handle();

        $this->assertSame(now()->getTimestamp(), Cache::get(QueueHeartbeatJob::CACHE_KEY));
    }
}
