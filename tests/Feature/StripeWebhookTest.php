<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Household;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function webhooksAreRefusedWhenNoSigningSecretIsConfigured(): void
    {
        config(['cashier.webhook.secret' => null]);
        $household = Household::factory()->create(['stripe_id' => 'cus_test123']);

        $this->postJson('/stripe/webhook', $this->forgedSubscriptionCreated('cus_test123'))
            ->assertStatus(503);

        $this->assertFalse($household->fresh()->subscribed('default'));
    }

    #[Test]
    public function unsignedWebhooksAreRejectedWhenASecretIsConfigured(): void
    {
        config(['cashier.webhook.secret' => 'whsec_test']);
        $household = Household::factory()->create(['stripe_id' => 'cus_test123']);

        $this->postJson('/stripe/webhook', $this->forgedSubscriptionCreated('cus_test123'))
            ->assertForbidden();

        $this->assertFalse($household->fresh()->subscribed('default'));
    }

    /** @return array<string, mixed> */
    private function forgedSubscriptionCreated(string $customer): array
    {
        return [
            'type' => 'customer.subscription.created',
            'data' => ['object' => [
                'id' => 'sub_forged',
                'customer' => $customer,
                'status' => 'active',
                'metadata' => ['type' => 'default'],
                'items' => ['data' => [[
                    'id' => 'si_forged',
                    'quantity' => 1,
                    'price' => ['id' => 'price_forged', 'product' => 'prod_forged'],
                ]]],
            ]],
        ];
    }
}
