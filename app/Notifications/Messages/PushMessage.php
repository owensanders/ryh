<?php

declare(strict_types=1);

namespace App\Notifications\Messages;

/**
 * What a push notification shows, as plain values — so the queued job that
 * delivers it never has to reload a chore or event that may since be deleted.
 */
final readonly class PushMessage
{
    public function __construct(
        public string $title,
        public string $body,
        public string $url,
        public string $tag,
    ) {}
}
