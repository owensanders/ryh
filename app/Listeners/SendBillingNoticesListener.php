<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Billing\SendPaymentFailedNoticeAction;
use App\Actions\Billing\SendRenewalReminderAction;
use Laravel\Cashier\Events\WebhookReceived;

/** The billing emails the Terms of Service promise, sent from Stripe's webhooks. */
class SendBillingNoticesListener
{
    public function __construct(
        private readonly SendRenewalReminderAction $renewalReminder,
        private readonly SendPaymentFailedNoticeAction $paymentFailed,
    ) {}

    public function handle(WebhookReceived $event): void
    {
        /** @var array<string, mixed> $invoice */
        $invoice = $event->payload['data']['object'] ?? [];

        match ($event->payload['type'] ?? null) {
            'invoice.upcoming' => $this->renewalReminder->handle($invoice),
            'invoice.payment_failed' => $this->paymentFailed->handle($invoice),
            default => null,
        };
    }
}
