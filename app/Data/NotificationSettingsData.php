<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** Settings → Notifications. */
class NotificationSettingsData extends Data
{
    /**
     * @param  array<int, PushCategoryData>  $categories
     */
    public function __construct(
        public array $categories,
        public ?string $vapidPublicKey,
    ) {}
}
