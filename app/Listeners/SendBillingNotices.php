<?php

declare(strict_types=1);

namespace App\Listeners;

use App\UseCases\Billing\SendPaymentFailedNoticeUseCase;
use App\UseCases\Billing\SendRenewalReminderUseCase;
use Laravel\Cashier\Events\WebhookReceived;

/** The billing emails the Terms of Service promise, sent from Stripe's webhooks. */
class SendBillingNotices
{
    public function __construct(
        private readonly SendRenewalReminderUseCase $renewalReminder,
        private readonly SendPaymentFailedNoticeUseCase $paymentFailed,
    ) {}

    public function handle(WebhookReceived $event): void
    {
        /** @var array<string, mixed> $invoice */
        $invoice = $event->payload['data']['object'] ?? [];

        match ($event->payload['type'] ?? null) {
            'invoice.upcoming' => $this->renewalReminder->execute($invoice),
            'invoice.payment_failed' => $this->paymentFailed->execute($invoice),
            default => null,
        };
    }
}
