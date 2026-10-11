<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * For queued email. Retries spread over about 20 minutes, so a mail provider
 * outage doesn't lose the email. Without a backoff the worker's three tries
 * all land within a second.
 */
trait RetriesEmailTrait
{
    public int $tries = 4;

    /** @return list<int> seconds to wait before each retry */
    public function backoff(): array
    {
        return [60, 300, 900];
    }
}
