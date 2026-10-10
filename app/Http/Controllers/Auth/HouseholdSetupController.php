<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\HouseholdJoinRequest;
use App\Http\Requests\Auth\HouseholdSetupRequest;
use App\Traits\ResolvesUserTrait;
use App\UseCases\House\CreateHouseholdUseCase;
use App\UseCases\House\JoinHouseholdUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HouseholdSetupController extends Controller
{
    use ResolvesUserTrait;

    public function create(Request $request): Response|RedirectResponse
    {
        if ($this->user($request)->currentMembership()?->pending === true) {
            return to_route('household.done');
        }

        return Inertia::render('auth/HouseholdSetup', [
            'trialAvailable' => $this->user($request)->trial_claimed_at === null,
            // Set when an invite link couldn't add them because the household had filled up.
            'fullHousehold' => $request->session()->get('fullHousehold'),
            // Set when a Teen or Child invite link predates guardian confirmation, so it can't add them.
            'reinviteHousehold' => $request->session()->get('reinviteHousehold'),
            'maxMembers' => config('plans.max_members'),
        ]);
    }

    public function store(HouseholdSetupRequest $request, CreateHouseholdUseCase $createHousehold): RedirectResponse
    {
        if ($this->user($request)->currentMembership()?->pending === true) {
            return to_route('household.done');
        }

        $household = $createHousehold->execute($this->user($request), $request->toData());

        return to_route('household.done')->with('householdSetup', [
            'mode' => 'create',
            'householdName' => $household->name,
            'inviteCount' => count($request->validated('invites', [])),
        ]);
    }

    public function join(HouseholdJoinRequest $request, JoinHouseholdUseCase $joinHousehold): RedirectResponse
    {
        if ($this->user($request)->currentMembership()?->pending === true) {
            return to_route('household.done');
        }

        $household = $joinHousehold->execute($this->user($request), (string) $request->validated('code'));

        return to_route('household.done')->with('householdSetup', [
            'mode' => 'join',
            'householdName' => $household->name,
            'inviteCount' => 0,
        ]);
    }

    public function done(Request $request): Response
    {
        /** @var array{mode: string, householdName: string, inviteCount: int}|null $setup */
        $setup = $request->session()->get('householdSetup');
        $user = $this->user($request);
        $household = $user->household;

        $pending = $user->currentMembership()?->pending === true;

        return Inertia::render('auth/HouseholdDone', [
            'mode' => $setup['mode'] ?? ($pending ? 'join' : 'create'),
            'householdName' => $setup['householdName'] ?? $household->name ?? '',
            'inviteCount' => $setup['inviteCount'] ?? 0,
            'pending' => $pending,
        ]);
    }
}
