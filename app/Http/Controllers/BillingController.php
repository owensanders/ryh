<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\PlanData;
use App\Http\Requests\SubscribeRequest;
use App\Traits\ResolvesHouseholdTrait;
use App\UseCases\House\CompleteCheckoutUseCase;
use App\UseCases\House\SubscribeHouseholdUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BillingController extends Controller
{
    use ResolvesHouseholdTrait;

    public function required(Request $request): Response|RedirectResponse
    {
        $household = $this->household($request);

        if (! $household->needsToSubscribe()) {
            return to_route('dashboard');
        }

        return Inertia::render('BillingRequired', [
            'plan' => PlanData::current(),
            'isOwner' => $request->user()->currentRole()?->value === 'owner',
            'paymentIssue' => $household->hasPaymentIssue(),
            'billingConfigured' => filled(config('plans.intervals.monthly.stripe_price'))
                && filled(config('plans.intervals.annual.stripe_price')),
        ]);
    }

    public function subscribe(SubscribeRequest $request, SubscribeHouseholdUseCase $subscribe): SymfonyResponse
    {
        $interval = $request->validated('interval');

        try {
            $checkoutUrl = $subscribe->execute(
                $this->household($request),
                interval: $interval,
                successUrl: route('billing.checkout.complete'),
                cancelUrl: route('billing.required'),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        return $checkoutUrl !== null ? Inertia::location($checkoutUrl) : to_route('dashboard');
    }

    /**
     * Where Stripe Checkout sends the owner back to. `from=house` returns
     * them to the House tab they started on; otherwise the dashboard.
     */
    public function complete(Request $request, CompleteCheckoutUseCase $complete): RedirectResponse
    {
        $household = $this->household($request);
        $sessionId = $request->string('session_id')->toString();

        if ($sessionId !== '') {
            try {
                $complete->execute($household, $sessionId);
            } catch (ApiErrorException $e) {
                // The webhook will still sync the subscription; just log it.
                Log::warning("Couldn't sync Stripe checkout session {$sessionId}: {$e->getMessage()}");
            }
        }

        return to_route($request->query('from') === 'house' ? 'house.index' : 'dashboard')
            ->with('toast', "You're subscribed. Thanks!");
    }

    public function portal(Request $request): SymfonyResponse
    {
        $household = $this->household($request);

        if (! $household->hasStripeId()) {
            return back()->withErrors(['plan' => 'There are no billing details to manage yet.']);
        }

        return Inertia::location($household->billingPortalUrl(route('house.index')));
    }
}
