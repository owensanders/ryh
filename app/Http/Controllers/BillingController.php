<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\PlanData;
use App\Http\Requests\SubscribeRequest;
use App\Traits\ResolvesHouseholdTrait;
use App\UseCases\House\SubscribeHouseholdUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BillingController extends Controller
{
    use ResolvesHouseholdTrait;

    public function required(Request $request): Response
    {
        return Inertia::render('BillingRequired', [
            'plan' => PlanData::current(),
            'isOwner' => $request->user()->currentRole()?->value === 'owner',
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
                successUrl: route('dashboard'),
                cancelUrl: route('billing.required'),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        return $checkoutUrl !== null ? Inertia::location($checkoutUrl) : to_route('dashboard');
    }
}
