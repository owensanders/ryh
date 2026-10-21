<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\HouseholdRoleEnum;
use App\Enums\InviteJoinOutcomeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\UseCases\Auth\JoinHouseholdFromInviteUseCase;
use App\UseCases\Auth\RegisterUserUseCase;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(Request $request): Response
    {
        // The landing page's call to action hands the email over in the query string.
        $email = $request->query('email');

        // A signed household-invite link also carries which household/role to
        // join — stash it in the session so it survives through to `store()`
        // without a tamperable hidden field round-tripping through the form.
        if ($request->hasValidSignature() && $request->query('invite_household') !== null) {
            $request->session()->put('pendingInvite', [
                'household_id' => (int) $request->query('invite_household'),
                'role' => (string) $request->query('invite_role'),
                // Set when the inviter confirmed they're a Teen or Child's parent or guardian.
                'guardian_id' => $request->query('invite_guardian') !== null ? (int) $request->query('invite_guardian') : null,
                'guardian_at' => $request->query('invite_guardian_at') !== null ? (int) $request->query('invite_guardian_at') : null,
            ]);
        }

        return Inertia::render('auth/Register', [
            'email' => is_string($email) ? $email : '',
            // Invited members may be children joining a parent's household, so
            // the terms box words its age statement differently for them.
            'invited' => $request->session()->has('pendingInvite'),
        ]);
    }

    public function store(
        RegisterRequest $request,
        RegisterUserUseCase $register,
        JoinHouseholdFromInviteUseCase $joinFromInvite,
    ): RedirectResponse {
        $user = $register->execute(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password'),
        );

        /** @var array{household_id: int, role: string, guardian_id?: int|null, guardian_at?: int|null}|null $invite */
        $invite = $request->session()->pull('pendingInvite');

        if ($invite === null) {
            return to_route('household.setup');
        }

        $result = $joinFromInvite->execute(
            $user,
            (int) $invite['household_id'],
            HouseholdRoleEnum::from($invite['role']),
            $invite['guardian_id'] ?? null,
            isset($invite['guardian_at']) ? CarbonImmutable::createFromTimestamp($invite['guardian_at']) : null,
        );

        return match ($result->outcome) {
            InviteJoinOutcomeEnum::Joined => to_route('app'),
            InviteJoinOutcomeEnum::NeedsGuardian => to_route('household.setup')->with('reinviteHousehold', $result->householdName),
            InviteJoinOutcomeEnum::HouseholdFull => to_route('household.setup')->with('fullHousehold', $result->householdName),
            InviteJoinOutcomeEnum::HouseholdGone => to_route('household.setup'),
        };
    }
}
