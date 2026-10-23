<?php

declare(strict_types=1);

namespace App\Inputs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Dto;

/** A browser's Web Push subscription, as StorePushSubscriptionRequest validated it (the shape PushSubscription.toJSON() sends). */
final class PushSubscriptionInput extends Dto
{
    public function __construct(
        public readonly string $endpoint,
        #[MapInputName('keys.p256dh')]
        public readonly string $publicKey,
        #[MapInputName('keys.auth')]
        public readonly string $authToken,
        public readonly ?string $contentEncoding = null,
    ) {}
}
