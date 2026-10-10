<?php

declare(strict_types=1);

namespace App\Status;

use App\Ai\AiConfigService;
use App\Enums\ServiceStateEnum;
use App\Jobs\QueueHeartbeatJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * One cheap, side-effect-free check per part of the platform. Each one swallows
 * its own failure — a probe reports a state, never an exception — so nothing
 * about the infrastructure can leak onto the public status page.
 */
class PlatformProbesService
{
    /** Jobs the worker hasn't picked up within this long mean the queue is stuck. */
    public const int QUEUE_GRACE_SECONDS = 120;

    private const string QUEUE_PROBE_KEY = 'status:queue-probe';

    private const int REALTIME_TIMEOUT_SECONDS = 2;

    public function database(): ServiceStateEnum
    {
        return $this->attempt(fn () => DB::select('select 1'));
    }

    public function cache(): ServiceStateEnum
    {
        return $this->attempt(function (): void {
            $token = bin2hex(random_bytes(8));
            Cache::put('status:cache-probe', $token, 60);

            if (Cache::get('status:cache-probe') !== $token) {
                throw new \RuntimeException('Cache did not return what was written.');
            }
        });
    }

    /**
     * Sends a heartbeat job through the queue and judges the worker by whether
     * the previous one came back. Jobs still flow while this is degraded, just
     * late — invite emails and notifications arrive once the worker catches up.
     */
    public function queue(): ServiceStateEnum
    {
        try {
            $now = now()->getTimestamp();
            $probe = Cache::get(self::QUEUE_PROBE_KEY);
            $heartbeat = Cache::get(QueueHeartbeatJob::CACHE_KEY);
            $answered = $probe !== null && $heartbeat !== null && $heartbeat >= $probe;

            $state = $probe !== null && ! $answered && $now - $probe > self::QUEUE_GRACE_SECONDS
                ? ServiceStateEnum::Degraded
                : ServiceStateEnum::Operational;

            // Only one heartbeat in flight at a time, so a stuck worker doesn't
            // pile up a backlog of them.
            if ($probe === null || $answered) {
                Cache::put(self::QUEUE_PROBE_KEY, $now, now()->addDay());
                QueueHeartbeatJob::dispatch();
            }

            return $state;
        } catch (Throwable) {
            return ServiceStateEnum::Down;
        }
    }

    /** Live notifications: can we open a connection to the Reverb server? */
    public function realtime(): ServiceStateEnum
    {
        $host = config('broadcasting.connections.reverb.options.host');
        $port = (int) config('broadcasting.connections.reverb.options.port');

        if (blank($host) || $port === 0) {
            return ServiceStateEnum::Down;
        }

        $socket = @fsockopen($host, $port, $errorCode, $errorMessage, self::REALTIME_TIMEOUT_SECONDS);

        if ($socket === false) {
            return ServiceStateEnum::Down;
        }

        fclose($socket);

        return ServiceStateEnum::Operational;
    }

    /** AI meal plans pause themselves app-wide when the provider runs out of credit. */
    public function ai(): ServiceStateEnum
    {
        return AiConfigService::isConfigured() && ! AiConfigService::isPaused()
            ? ServiceStateEnum::Operational
            : ServiceStateEnum::Degraded;
    }

    public function documents(): ServiceStateEnum
    {
        return $this->attempt(fn () => Storage::disk(config('documents.disk'))->exists('.status-probe'));
    }

    private function attempt(callable $check): ServiceStateEnum
    {
        try {
            $check();

            return ServiceStateEnum::Operational;
        } catch (Throwable) {
            return ServiceStateEnum::Down;
        }
    }
}
