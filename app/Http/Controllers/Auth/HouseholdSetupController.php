<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\House\CreateHouseholdAction;
use App\Actions\House\JoinHouseholdAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\HouseholdJoinRequest;
use App\Http\Requests\Auth\HouseholdSetupRequest;
use App\Traits\ResolvesUserTrait;
use App\ViewModels\Auth\HouseholdDoneViewModel;
use App\ViewModels\Auth\HouseholdSetupViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HouseholdSetupController extends Controller
{
    use ResolvesUserTrait;

    public function create(Request $request, HouseholdSetupViewModel $setup): Response
    {
        return Inertia::render('auth/HouseholdSetup', $setup->toData(
            $this->user($request),
            // Flashed when an invite link couldn't add them: the household was full, or the
            // Teen or Child invite predates guardian confirmation.
            fullHousehold: $request->session()->get('fullHousehold'),
            reinviteHousehold: $request->session()->get('reinviteHousehold'),
        ));
    }

    public function store(HouseholdSetupRequest $request, CreateHouseholdAction $createHousehold): RedirectResponse
    {
        $setup = $request->toInput();
        $household = $createHousehold->handle($this->user($request), $setup);

        return to_route('household.done')->with('householdSetup', [
            'mode' => 'create',
            'householdName' => $household->name,
            'inviteCount' => count($setup->invites),
        ]);
    }

    public function join(HouseholdJoinRequest $request, JoinHouseholdAction $joinHousehold): RedirectResponse
    {
        $household = $joinHousehold->handle($this->user($request), $request->code());

        return to_route('household.done')->with('householdSetup', [
            'mode' => 'join',
            'householdName' => $household->name,
            'inviteCount' => 0,
        ]);
    }

    public function done(Request $request, HouseholdDoneViewModel $done): Response
    {
        return Inertia::render('auth/HouseholdDone', $done->toData(
            $this->user($request),
            $request->session()->get('householdSetup'),
        ));
    }
}
