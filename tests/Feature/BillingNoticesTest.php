<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRoleEnum;
use App\Mail\PaymentFailedMail;
use App\Mail\RenewalReminderMail;
use App\Models\Household;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** The renewal reminder and failed-payment emails promised in the Terms of Service. */
class BillingNoticesTest extends TestCase
{
    use RefreshDatabase;

    private const string SECRET = 'whsec_test';

    private User $owner;

    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['plans.intervals.annual.stripe_price' => 'price_annual', 'plans.intervals.monthly.stripe_price' => 'price_monthly']);

        $this->owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $this->household = $this->owner->lastHousehold;
        $this->household->forceFill(['stripe_id' => 'cus_test123'])->save();
    }

    private function subscribe(string $price, ?DateTimeInterface $endsAt = null): void
    {
        Subscription::create([
            'household_id' => $this->household->id,
            'type' => 'default',
            'stripe_id' => 'sub_test123',
            'stripe_status' => 'active',
            'stripe_price' => $price,
            'ends_at' => $endsAt,
        ]);
    }

    /**
     * @param  array<string, mixed>  $object
     *
     * @return TestResponse<Response>
     */
    private function postSigned(string $type, array $object, string $eventId = 'evt_test'): TestResponse
    {
        config(['cashier.webhook.secret' => self::SECRET]);

        $payload = json_encode(['id' => $eventId, 'type' => $type, 'data' => ['object' => $object]], JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", self::SECRET);

        return $this->call('POST', '/stripe/webhook', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], content: $payload);
    }

    /** @return array<string, mixed> */
    private function upcomingInvoice(): array
    {
        return [
            'object' => 'invoice',
            'customer' => 'cus_test123',
            'amount_due' => 3999,
            'currency' => 'gbp',
            'next_payment_attempt' => now()->addDays(14)->startOfDay()->getTimestamp(),
        ];
    }

    #[Test]
    public function aRepeatedDeliveryOfTheSameEventSendsOneReminderButANewEventSendsAnother(): void
    {
        $this->subscribe('price_annual');

        $this->postSigned('invoice.upcoming', $this->upcomingInvoice(), 'evt_once')->assertOk();
        $this->postSigned('invoice.upcoming', $this->upcomingInvoice(), 'evt_once')->assertOk()->assertSeeText('Webhook already handled');

        Mail::assertQueued(RenewalReminderMail::class, 1);

        $this->postSigned('invoice.upcoming', $this->upcomingInvoice(), 'evt_next_year')->assertOk();

        Mail::assertQueued(RenewalReminderMail::class, 2);
    }

    #[Test]
    public function ownersAreRemindedBeforeAnAnnualRenewal(): void
    {
        $this->subscribe('price_annual');
        $adult = User::factory()->create(['household_id' => $this->household->id, 'role' => HouseholdRoleEnum::Adult]);

        $this->postSigned('invoice.upcoming', $this->upcomingInvoice())->assertOk();

        Mail::assertQueued(RenewalReminderMail::class, fn (RenewalReminderMail $mail) => $mail->hasTo($this->owner->email)
            && $mail->amountLabel === '£39.99'
            && $mail->renewsOnLabel === now()->addDays(14)->format('j F Y'));
        Mail::assertNotQueued(RenewalReminderMail::class, fn (RenewalReminderMail $mail) => $mail->hasTo($adult->email));
    }

    #[Test]
    public function monthlyPlansAreNotReminded(): void
    {
        $this->subscribe('price_monthly');

        $this->postSigned('invoice.upcoming', $this->upcomingInvoice())->assertOk();

        Mail::assertNotQueued(RenewalReminderMail::class);
    }

    #[Test]
    public function aCancelledSubscriptionIsNotReminded(): void
    {
        // Cancelled, running to the end of its paid year — it won't renew.
        $this->subscribe('price_annual', endsAt: now()->addDays(14));

        $this->postSigned('invoice.upcoming', $this->upcomingInvoice())->assertOk();

        Mail::assertNotQueued(RenewalReminderMail::class);
    }

    #[Test]
    public function ownersAreToldWhenARenewalPaymentFails(): void
    {
        $this->subscribe('price_monthly');

        $this->postSigned('invoice.payment_failed', [
            'object' => 'invoice',
            'customer' => 'cus_test123',
            'billing_reason' => 'subscription_cycle',
            'attempt_count' => 1,
        ])->assertOk();

        Mail::assertQueued(PaymentFailedMail::class, fn (PaymentFailedMail $mail) => $mail->hasTo($this->owner->email));
    }

    #[Test]
    public function stripesRetriesDoNotSendTheEmailAgain(): void
    {
        $this->subscribe('price_monthly');

        $this->postSigned('invoice.payment_failed', [
            'object' => 'invoice',
            'customer' => 'cus_test123',
            'billing_reason' => 'subscription_cycle',
            'attempt_count' => 2,
        ])->assertOk();

        Mail::assertNotQueued(PaymentFailedMail::class);
    }

    #[Test]
    public function aFailedFirstPaymentInCheckoutIsNotEmailed(): void
    {
        $this->postSigned('invoice.payment_failed', [
            'object' => 'invoice',
            'customer' => 'cus_test123',
            'billing_reason' => 'subscription_create',
            'attempt_count' => 1,
        ])->assertOk();

        Mail::assertNotQueued(PaymentFailedMail::class);
    }

    #[Test]
    public function eventsForAnUnknownCustomerAreIgnored(): void
    {
        $this->subscribe('price_annual');

        $this->postSigned('invoice.upcoming', [...$this->upcomingInvoice(), 'customer' => 'cus_someone_else'])->assertOk();

        Mail::assertNothingQueued();
    }

    #[Test]
    public function theEmailsRender(): void
    {
        $this->assertStringContainsString('£39.99', (new RenewalReminderMail($this->household, '£39.99', '1 November 2026'))->render());
        $this->assertStringContainsString('Update payment details', (new PaymentFailedMail($this->household))->render());
    }
}
