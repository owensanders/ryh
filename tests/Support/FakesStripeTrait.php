<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\After;
use Stripe\ApiRequestor;
use Stripe\HttpClient\CurlClient;
use Symfony\Component\HttpFoundation\Response;

trait FakesStripeTrait
{
    private static string $stripeWebhookSecret = 'whsec_test';

    protected function fakeStripe(): FakeStripeClient
    {
        config(['cashier.secret' => 'sk_test_fake']);

        $fake = new FakeStripeClient();
        ApiRequestor::setHttpClient($fake);

        return $fake;
    }

    #[After]
    protected function restoreStripeHttpClient(): void
    {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    /**
     * A Stripe API object as the API returns it (tests/Fixtures/Stripe), with
     * `$overrides` laid over it: objects merge key by key, lists are replaced.
     *
     * @param  array<string, mixed>  $overrides
     *
     * @return array<string, mixed>
     */
    protected function stripeFixture(string $object, array $overrides = []): array
    {
        /** @var array<string, mixed> $fixture */
        $fixture = json_decode((string) file_get_contents(base_path("tests/Fixtures/Stripe/{$object}.json")), true, flags: JSON_THROW_ON_ERROR);

        return self::overlay($fixture, $overrides);
    }

    /**
     * A Stripe Subscription with one item on `$price`, as the API returns it.
     *
     * @return array<string, mixed>
     */
    protected function stripeSubscription(string $id, string $customer, string $price, string $status = 'active'): array
    {
        return $this->stripeFixture('subscription', [
            'id' => $id,
            'customer' => $customer,
            'status' => $status,
            'items' => [
                'url' => "/v1/subscription_items?subscription={$id}",
                'data' => [$this->stripeFixture('subscription_item', [
                    'id' => 'si_'.substr($id, 4),
                    'subscription' => $id,
                    'price' => ['id' => $price],
                ])],
            ],
        ]);
    }

    /**
     * Posts a signed Stripe event to Cashier's webhook, as Stripe delivers it.
     *
     * @param  array<string, mixed>  $object  The event's data.object.
     *
     * @return TestResponse<Response>
     */
    protected function postStripeWebhook(string $type, array $object, string $eventId = 'evt_test'): TestResponse
    {
        config(['cashier.webhook.secret' => self::$stripeWebhookSecret]);

        $payload = json_encode($this->stripeFixture('event', ['id' => $eventId, 'type' => $type, 'data' => ['object' => $object]]), JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", self::$stripeWebhookSecret);

        return $this->call('POST', '/stripe/webhook', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], content: $payload);
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $overrides
     *
     * @return array<string, mixed>
     */
    private static function overlay(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            $base[$key] = is_array($value) && ! array_is_list($value) && is_array($base[$key] ?? null) && ! array_is_list($base[$key])
                ? self::overlay($base[$key], $value)
                : $value;
        }

        return $base;
    }
}
