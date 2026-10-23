<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\JoinHouseholdFromInviteAction;
use App\Actions\Auth\RegisterUserAction;
use App\Enums\InviteJoinOutcomeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ShowRegisterRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(ShowRegisterRequest $request): Response
    {
        $request->rememberInvite();

        return Inertia::render('auth/Register', ['email' => $request->email(), 'invited' => $request->isInvited()]);
    }

    public function store(
        RegisterRequest $request,
        RegisterUserAction $register,
        JoinHouseholdFromInviteAction $joinFromInvite,
    ): RedirectResponse {
        $user = $register->handle($request->toInput());
        $invite = $request->pendingInvite();

        if ($invite === null) {
            return to_route('household.setup');
        }

        $result = $joinFromInvite->handle($user, $invite);

        return match ($result->outcome) {
            InviteJoinOutcomeEnum::Joined => to_route('app'),
            InviteJoinOutcomeEnum::NeedsGuardian => to_route('household.setup')->with('reinviteHousehold', $result->householdName),
            InviteJoinOutcomeEnum::HouseholdFull => to_route('household.setup')->with('fullHousehold', $result->householdName),
            InviteJoinOutcomeEnum::HouseholdGone => to_route('household.setup'),
        };
    }
}
