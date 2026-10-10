<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Traits\ResolvesUserTrait;
use App\UseCases\House\AcceptHouseholdInviteUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HouseholdInviteController extends Controller
{
    use ResolvesUserTrait;

    public function accept(Request $request, AcceptHouseholdInviteUseCase $accept): RedirectResponse
    {
        abort_if($this->user($request)->id !== (int) $request->query('member'), 403);

        $householdName = $accept->execute($this->user($request), (int) $request->query('household'));

        return to_route('household.select')->with('toast', "You're in {$householdName} — pick a household to view");
    }
}
