<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\Attributes\After;
use Stripe\ApiRequestor;
use Stripe\HttpClient\CurlClient;

trait FakesStripe
{
    protected function fakeStripe(): FakeStripeClient
    {
        config(['cashier.secret' => 'sk_test_fake']);

        $fake = new FakeStripeClient;
        ApiRequestor::setHttpClient($fake);

        return $fake;
    }

    #[After]
    protected function restoreStripeHttpClient(): void
    {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    /**
     * A Stripe Subscription object, as the API returns it.
     *
     * @return array<string, mixed>
     */
    protected function stripeSubscription(string $id, string $customer, string $price, string $status = 'active'): array
    {
        return [
            'id' => $id,
            'object' => 'subscription',
            'customer' => $customer,
            'status' => $status,
            'trial_end' => null,
            'cancel_at_period_end' => false,
            'metadata' => ['type' => 'default', 'name' => 'default'],
            'items' => [
                'object' => 'list',
                'data' => [[
                    'id' => 'si_'.substr($id, 4),
                    'object' => 'subscription_item',
                    'quantity' => 1,
                    'price' => ['id' => $price, 'object' => 'price', 'product' => 'prod_home'],
                ]],
            ],
        ];
    }
}
