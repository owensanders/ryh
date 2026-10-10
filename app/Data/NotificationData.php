<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Notifications\DatabaseNotification;
use Spatie\LaravelData\Data;

/** A bell entry, in the shape `useNotifications.ts` reads (`NotificationRow`). */
class NotificationData extends Data
{
    /** @param  array<string, mixed>  $data  The notification's toArray(): body, url, type… */
    public function __construct(
        public string $id,
        public array $data,
        public ?string $read_at,
    ) {}

    public static function fromModel(DatabaseNotification $notification): self
    {
        /** @var array<string, mixed> $data */
        $data = $notification->data;

        return new self($notification->id, $data, $notification->read_at?->toJSON());
    }
}
