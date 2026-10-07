<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/** A no-op job the status page sends through the queue to prove a worker is picking jobs up. */
class QueueHeartbeat implements ShouldQueue
{
    use Queueable;

    public const CACHE_KEY = 'status:queue-heartbeat';

    public int $tries = 1;

    public function handle(): void
    {
        Cache::put(self::CACHE_KEY, now()->getTimestamp(), now()->addDay());
    }
}
