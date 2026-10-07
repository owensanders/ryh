<?php

declare(strict_types=1);

namespace App\UseCases\Billing;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Mail\PaymentFailedMail;
use Illuminate\Support\Facades\Mail;

/**
 * Emails a household's Owners when a renewal payment fails, as the Terms of
 * Service promise. Driven by Stripe's `invoice.payment_failed` event.
 */
class SendPaymentFailedNoticeUseCase
{
    public function __construct(private readonly HouseholdRepositoryInterface $households) {}

    /** @param  array<string, mixed>  $invoice  The failed invoice from the webhook payload. */
    public function execute(array $invoice): void
    {
        // A failed first payment happens in Checkout, in front of them. And Stripe
        // retries a failed renewal several times — one email is enough.
        if (($invoice['billing_reason'] ?? null) === 'subscription_create' || (int) ($invoice['attempt_count'] ?? 1) > 1) {
            return;
        }

        $customer = $invoice['customer'] ?? null;
        $household = is_string($customer) ? $this->households->findByStripeCustomer($customer) : null;

        if ($household === null) {
            return;
        }

        foreach ($this->households->activeOwners($household) as $owner) {
            Mail::to($owner->email)->queue(new PaymentFailedMail($household));
        }
    }
}
