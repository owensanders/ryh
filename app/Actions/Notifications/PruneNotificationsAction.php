<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Notifications\DatabaseNotification;

class PruneNotificationsAction
{
    private const int BATCH = 1000;

    /**
     * Deletes bell notifications created before `$cutoff`, in batches, so a
     * big first prune never holds a long lock on the table.
     *
     * @return int How many were deleted.
     */
    public function handle(CarbonImmutable $cutoff): int
    {
        $deleted = 0;

        do {
            $batch = DatabaseNotification::query()->where('created_at', '<', $cutoff)->limit(self::BATCH)->delete();
            $deleted += $batch;
        } while ($batch > 0);

        return $deleted;
    }
}
