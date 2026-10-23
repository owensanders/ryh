<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\House\LeaveHouseholdAction;
use App\Actions\House\SwitchCurrentHouseholdAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\HouseholdSelectRequest;
use App\Http\Requests\Settings\LeaveHouseholdRequest;
use App\Traits\ResolvesUserTrait;
use App\ViewModels\House\HouseholdOptionsViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HouseholdController extends Controller
{
    use ResolvesUserTrait;

    public function edit(Request $request, HouseholdOptionsViewModel $getOptions): Response
    {
        return Inertia::render('settings/Households', [
            'households' => $getOptions->toData($this->user($request)),
            'currentHouseholdId' => $this->user($request)->last_household_id,
        ]);
    }

    public function switchCurrent(HouseholdSelectRequest $request, SwitchCurrentHouseholdAction $switch): RedirectResponse
    {
        $switch->handle($this->user($request), $request->householdId());

        return to_route('app');
    }

    public function leave(LeaveHouseholdRequest $request, LeaveHouseholdAction $leave, HouseholdOptionsViewModel $getOptions): RedirectResponse
    {
        $householdName = $leave->handle($this->user($request), $request->toInput());

        if ($getOptions->toData($this->user($request)) === []) {
            return to_route('household.setup');
        }

        return back()->with('toast', "You left {$householdName}");
    }
}
