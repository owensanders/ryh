<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HouseholdRole;
use App\Http\Requests\InviteRequest;
use App\Http\Requests\JoinCodeToggleRequest;
use App\Http\Requests\RoleRequest;
use App\Http\Requests\SubscribeRequest;
use App\Models\Household;
use App\Models\User;
use App\Traits\ResolvesHouseholdTrait;
use App\UseCases\House\ApproveMemberUseCase;
use App\UseCases\House\ChangeMemberRoleUseCase;
use App\UseCases\House\GetHouseUseCase;
use App\UseCases\House\InviteMemberUseCase;
use App\UseCases\House\RegenerateJoinCodeUseCase;
use App\UseCases\House\RemoveMemberUseCase;
use App\UseCases\House\SubscribeHouseholdUseCase;
use App\UseCases\House\ToggleJoinCodeUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class HouseController extends Controller
{
    use ResolvesHouseholdTrait;

    public function index(Request $request, GetHouseUseCase $getHouse): Response
    {
        return Inertia::render('House', [
            'house' => $getHouse->execute($this->household($request), $request->user()->id),
        ]);
    }

    public function invite(InviteRequest $request, InviteMemberUseCase $invite): RedirectResponse
    {
        $attributes = $request->inviteAttributes();
        $invite->execute($this->household($request), $attributes, $request->user());

        return back()->with('toast', "Invite sent to {$attributes['email']}");
    }

    public function updateRole(RoleRequest $request, User $member, ChangeMemberRoleUseCase $change): RedirectResponse
    {
        $household = $this->household($request);
        $this->assertOwned($member, $household);

        $role = HouseholdRole::from($request->validated('role'));
        $updated = $change->execute($member, $household, $role, $request->user());

        return back()->with('toast', "{$updated->name} is now {$role->label()}");
    }

    public function approve(Request $request, User $member, ApproveMemberUseCase $approve): RedirectResponse
    {
        $household = $this->household($request);
        $this->assertOwned($member, $household);

        $approved = $approve->execute($member, $household);

        return back()->with('toast', "{$approved->name} approved — welcome to the household");
    }

    public function destroy(Request $request, User $member, RemoveMemberUseCase $remove): RedirectResponse
    {
        $household = $this->household($request);
        $this->assertOwned($member, $household);

        $name = $member->name;
        $wasPending = $member->households()->where('households.id', $household->id)->first()?->pivot->pending === true;
        $remove->execute($member, $household);

        return back()->with('toast', $wasPending ? "{$name} — invite cancelled" : "{$name} removed from the household");
    }

    public function toggleJoinCode(JoinCodeToggleRequest $request, ToggleJoinCodeUseCase $toggle): RedirectResponse
    {
        $toggle->execute($this->household($request), (bool) $request->validated('enabled'));

        return back()->with('toast', $request->boolean('enabled') ? 'Invite code turned on' : 'Invite code turned off');
    }

    public function regenerateJoinCode(Request $request, RegenerateJoinCodeUseCase $regenerate): RedirectResponse
    {
        $regenerate->execute($this->household($request));

        return back()->with('toast', 'New invite code generated');
    }

    public function subscribe(SubscribeRequest $request, SubscribeHouseholdUseCase $subscribe): SymfonyResponse
    {
        $interval = $request->validated('interval');

        try {
            $checkoutUrl = $subscribe->execute(
                $this->household($request),
                interval: $interval,
                successUrl: route('billing.checkout.complete', ['from' => 'house']),
                cancelUrl: route('house.index'),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        if ($checkoutUrl !== null) {
            return Inertia::location($checkoutUrl);
        }

        return back()->with('toast', 'You are already subscribed');
    }

    private function assertOwned(User $member, Household $household): void
    {
        abort_if(! $member->households()->where('households.id', $household->id)->exists(), 404);
    }
}
