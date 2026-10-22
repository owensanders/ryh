<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Billing\HandleStripeEventOnceAction;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

/** Cashier's webhook handling, but each Stripe event only once, however often it's delivered. */
class StripeWebhookController extends WebhookController
{
    public function __construct(
        private readonly HandleStripeEventOnceAction $handleOnce,
    ) {
        // Cashier's constructor adds its signature check.
        parent::__construct();
    }

    public function handleWebhook(Request $request): Response
    {
        $eventId = $request->json('id');

        if (! is_string($eventId) || $eventId === '') {
            return parent::handleWebhook($request);
        }

        $response = new Response('Webhook already handled');

        $this->handleOnce->handle(
            $eventId,
            (string) $request->json('type'),
            function () use ($request, &$response): void {
                $response = parent::handleWebhook($request);
            },
        );

        return $response;
    }
}
