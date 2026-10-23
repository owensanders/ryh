<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\House\CompleteCheckoutAction;
use App\Actions\House\SubscribeHouseholdAction;
use App\Http\Requests\CompleteCheckoutRequest;
use App\Http\Requests\SubscribeRequest;
use App\Traits\ResolvesHouseholdTrait;
use App\Traits\ResolvesUserTrait;
use App\ViewModels\Billing\BillingRequiredViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BillingController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    public function required(Request $request, BillingRequiredViewModel $billingRequired): Response|RedirectResponse
    {
        $household = $this->household($request);

        if (! $household->needsToSubscribe()) {
            return to_route('dashboard');
        }

        return Inertia::render('BillingRequired', $billingRequired->toData($household, $this->user($request)));
    }

    public function subscribe(SubscribeRequest $request, SubscribeHouseholdAction $subscribe): SymfonyResponse
    {
        // The House tab and the paywall both subscribe here; each gets sent back to where it started.
        $fromHouse = $request->fromHouse();

        $checkoutUrl = $subscribe->handle(
            $this->household($request),
            interval: $request->billingInterval(),
            successUrl: route('billing.checkout.complete', $fromHouse ? ['from' => 'house'] : []),
            cancelUrl: route($fromHouse ? 'house.index' : 'billing.required'),
        );

        if ($checkoutUrl !== null) {
            return Inertia::location($checkoutUrl);
        }

        return $fromHouse ? back()->with('toast', 'You are already subscribed') : to_route('dashboard');
    }

    /**
     * Where Stripe Checkout sends the owner back to. `from=house` returns
     * them to the House tab they started on; otherwise the dashboard.
     */
    public function complete(CompleteCheckoutRequest $request, CompleteCheckoutAction $complete): RedirectResponse
    {
        $household = $this->household($request);
        $sessionId = $request->sessionId();

        if ($sessionId !== null) {
            try {
                $complete->handle($household, $sessionId);
            } catch (ApiErrorException $e) {
                // The webhook still records the subscription; the paywall just can't say it's on its way.
                Log::warning("Couldn't check Stripe checkout session {$sessionId}: {$e->getMessage()}");
            }
        }

        // Only say so once it's true; otherwise the webhook is still on its way.
        return to_route($request->fromHouse() ? 'house.index' : 'dashboard')
            ->with('toast', $household->subscribed('default') ? "You're subscribed. Thanks!" : "Thanks! We're confirming your payment with Stripe; your plan will update in a moment.");
    }

    public function portal(Request $request): SymfonyResponse
    {
        $household = $this->household($request);

        if (! $household->hasStripeId()) {
            return back()->withErrors(['plan' => 'There are no billing details to manage yet.']);
        }

        try {
            return Inertia::location($household->billingPortalUrl(route('house.index')));
        } catch (ApiErrorException $e) {
            Log::error("Couldn't open the Stripe billing portal for household {$household->id}: {$e->getMessage()}");

            return back()->withErrors(['plan' => "We couldn't reach our payment provider. Please try again in a moment."]);
        }
    }
}
