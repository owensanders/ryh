<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Models\StripeWebhookEvent;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Handles a Stripe webhook event at most once. The event's ID is recorded in
 * the same transaction as its handling: a repeat delivery finds it and is
 * skipped; a second delivery arriving mid-way waits on the first's row
 * and then finds it; and a handling that fails rolls the record back, so
 * Stripe's retry gets another go.
 */
class HandleStripeEventOnceAction
{
    /**
     * @param  Closure(): void  $handle
     *
     * @return bool False when it had already been handled, and `$handle` wasn't run.
     */
    public function handle(string $eventId, string $type, Closure $handle): bool
    {
        return DB::transaction(function () use ($eventId, $type, $handle): bool {
            $recorded = StripeWebhookEvent::query()->insertOrIgnore([
                'id' => $eventId,
                'type' => $type,
                'created_at' => now(),
            ]);

            if ($recorded === 0) {
                return false;
            }

            $handle();

            return true;
        });
    }
}
