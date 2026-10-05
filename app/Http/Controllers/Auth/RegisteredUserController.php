<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\HouseholdRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Household;
use App\UseCases\Auth\JoinHouseholdFromInviteUseCase;
use App\UseCases\Auth\RegisterUserUseCase;
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
            ]);
        }

        return Inertia::render('auth/Register', [
            'email' => is_string($email) ? $email : '',
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

        /** @var array{household_id: int, role: string}|null $invite */
        $invite = $request->session()->pull('pendingInvite');
        $household = $invite !== null ? Household::find($invite['household_id']) : null;

        if ($household !== null) {
            if ($joinFromInvite->execute($user, $household, HouseholdRole::from($invite['role']))) {
                return to_route('dashboard');
            }

            return to_route('household.setup')->with('fullHousehold', $household->name);
        }

        return to_route('household.setup');
    }
}
