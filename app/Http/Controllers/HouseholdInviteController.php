<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\House\AcceptHouseholdInviteAction;
use App\Http\Requests\AcceptHouseholdInviteRequest;
use App\Traits\ResolvesUserTrait;
use Illuminate\Http\RedirectResponse;

class HouseholdInviteController extends Controller
{
    use ResolvesUserTrait;

    public function accept(AcceptHouseholdInviteRequest $request, AcceptHouseholdInviteAction $accept): RedirectResponse
    {
        $householdName = $accept->handle($this->user($request), $request->householdId());

        return to_route('household.select')->with('toast', "You're in {$householdName} — pick a household to view");
    }
}
