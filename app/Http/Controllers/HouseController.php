<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\House\ApproveMemberAction;
use App\Actions\House\ChangeMemberRoleAction;
use App\Actions\House\InviteMemberAction;
use App\Actions\House\RegenerateJoinCodeAction;
use App\Actions\House\RemoveMemberAction;
use App\Actions\House\SubscribeHouseholdAction;
use App\Actions\House\ToggleJoinCodeAction;
use App\Enums\HouseholdRoleEnum;
use App\Http\Requests\InviteRequest;
use App\Http\Requests\JoinCodeToggleRequest;
use App\Http\Requests\RoleRequest;
use App\Http\Requests\SubscribeRequest;
use App\Models\User;
use App\Traits\ResolvesHouseholdTrait;
use App\Traits\ResolvesUserTrait;
use App\ViewModels\House\HouseViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class HouseController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    public function index(Request $request, HouseViewModel $getHouse): Response
    {
        return Inertia::render('House', [
            'house' => $getHouse->toData($this->household($request), $this->user($request)->id),
        ]);
    }

    public function invite(InviteRequest $request, InviteMemberAction $invite): RedirectResponse
    {
        $input = $request->toData();
        $invite->handle($this->household($request), $input, $this->user($request));

        return back()->with('toast', "Invite sent to {$input->email}");
    }

    public function updateRole(RoleRequest $request, User $member, ChangeMemberRoleAction $change): RedirectResponse
    {
        $household = $this->household($request);

        $role = HouseholdRoleEnum::from($request->validated('role'));
        $change->handle($member, $household, $role, $this->user($request));

        return back()->with('toast', "{$member->name} is now {$role->label()}");
    }

    public function approve(Request $request, User $member, ApproveMemberAction $approve): RedirectResponse
    {
        $household = $this->household($request);

        $approve->handle($member, $household);

        return back()->with('toast', "{$member->name} approved — welcome to the household");
    }

    public function destroy(Request $request, User $member, RemoveMemberAction $remove): RedirectResponse
    {
        $household = $this->household($request);

        $name = $member->name;
        $wasPending = $remove->handle($member, $household);

        return back()->with('toast', $wasPending ? "{$name} — invite cancelled" : "{$name} removed from the household");
    }

    public function toggleJoinCode(JoinCodeToggleRequest $request, ToggleJoinCodeAction $toggle): RedirectResponse
    {
        $toggle->handle($this->household($request), (bool) $request->validated('enabled'));

        return back()->with('toast', $request->boolean('enabled') ? 'Invite code turned on' : 'Invite code turned off');
    }

    public function regenerateJoinCode(Request $request, RegenerateJoinCodeAction $regenerate): RedirectResponse
    {
        $regenerate->handle($this->household($request));

        return back()->with('toast', 'New invite code generated');
    }

    public function subscribe(SubscribeRequest $request, SubscribeHouseholdAction $subscribe): SymfonyResponse
    {
        $interval = $request->validated('interval');

        $checkoutUrl = $subscribe->handle(
            $this->household($request),
            interval: $interval,
            successUrl: route('billing.checkout.complete', ['from' => 'house']),
            cancelUrl: route('house.index'),
        );

        if ($checkoutUrl !== null) {
            return Inertia::location($checkoutUrl);
        }

        return back()->with('toast', 'You are already subscribed');
    }
}
