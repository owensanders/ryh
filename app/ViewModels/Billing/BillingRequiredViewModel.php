<?php

declare(strict_types=1);

namespace App\ViewModels\Billing;

use App\Data\BillingRequiredScreenData;
use App\Data\PlanData;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class BillingRequiredViewModel
{
    public function toData(Household $household, User $viewer): BillingRequiredScreenData
    {
        return new BillingRequiredScreenData(
            plan: PlanData::current(),
            isOwner: Gate::forUser($viewer)->allows('manage', $household),
            paymentIssue: $household->hasPaymentIssue(),
            billingConfigured: filled(config('plans.intervals.monthly.stripe_price'))
                && filled(config('plans.intervals.annual.stripe_price')),
            confirmingPayment: $household->isConfirmingPayment(),
        );
    }
}
