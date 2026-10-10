<?php

declare(strict_types=1);

namespace App\UseCases\Billing;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Mail\RenewalReminderMail;
use App\Models\Household;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;

/**
 * Emails a household's Owners before an annual subscription renews, as the
 * Terms of Service promise. Driven by Stripe's `invoice.upcoming` event, which
 * Stripe sends a set number of days before each renewal (configured in the
 * Stripe dashboard under Billing → Subscriptions → Upcoming renewal events).
 */
class SendRenewalReminderUseCase
{
    public function __construct(
        private readonly HouseholdRepositoryInterface $households,
        private readonly MembershipRepositoryInterface $memberships,
    ) {}

    /** @param  array<string, mixed>  $invoice  The upcoming invoice from the webhook payload. */
    public function execute(array $invoice): void
    {
        $customer = $invoice['customer'] ?? null;
        $household = is_string($customer) ? $this->households->findByStripeCustomer($customer) : null;

        if ($household === null || ! $this->renewsAnnually($household)) {
            return;
        }

        $renewsAt = $invoice['next_payment_attempt'] ?? data_get($invoice, 'lines.data.0.period.start');

        if (! is_int($renewsAt)) {
            return;
        }

        $amount = (int) ($invoice['amount_due'] ?? 0) / 100;
        $renewsOn = CarbonImmutable::createFromTimestamp($renewsAt)->format('j F Y');

        foreach ($this->memberships->activeOwners($household) as $owner) {
            Mail::to($owner->email)->queue(new RenewalReminderMail($household, '£'.number_format($amount, 2), $renewsOn));
        }
    }

    /** Monthly plans don't get a reminder, and a cancelled subscription isn't going to renew. */
    private function renewsAnnually(Household $household): bool
    {
        $subscription = $household->subscription('default');
        $annualPrice = config('plans.intervals.annual.stripe_price');

        return $subscription !== null
            && $subscription->valid()
            && ! $subscription->onGracePeriod()
            && is_string($annualPrice)
            && $subscription->hasPrice($annualPrice);
    }
}
