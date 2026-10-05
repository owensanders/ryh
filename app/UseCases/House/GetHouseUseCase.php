<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Data\HouseData;
use App\Data\HouseMemberData;
use App\Data\PlanStatusData;
use App\Data\RoleSummaryData;
use App\Data\StatData;
use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\User;

class GetHouseUseCase
{
    public function __construct(
        private readonly HouseholdRepositoryInterface $households,
    ) {}

    public function execute(Household $household, int $viewerId): HouseData
    {
        $members = $this->households->members($household);
        $activeCount = $members->where('pivot.pending', false)->count();
        $pendingCount = $members->where('pivot.pending', true)->count();
        $planStatus = PlanStatusData::fromHousehold($household);

        return new HouseData(
            houseName: $household->name,
            houseCreated: $household->created_at->format('F Y'),
            joinCode: $household->join_code ?? '',
            joinCodeEnabled: $household->join_code_enabled,
            houseStats: [
                new StatData('Members', (string) $activeCount),
                new StatData('Invites out', (string) $pendingCount),
                new StatData('Plan', $planStatus->subscribed ? config('plans.name') : ($planStatus->onTrial ? 'Free trial' : 'Trial ended')),
            ],
            memberCount: $members->count(),
            maxMembers: config('plans.max_members'),
            roleOptions: array_map(
                fn (HouseholdRole $role) => ['value' => $role->value, 'label' => $role->label()],
                HouseholdRole::cases(),
            ),
            members: $members->map(fn (User $user) => HouseMemberData::fromModel($user, $viewerId))->values()->all(),
            roles: array_map(
                fn (HouseholdRole $role) => new RoleSummaryData(
                    name: $role->label(),
                    colour: $role->colour()->cssVar(),
                    body: $role->description(),
                    count: $members->where('pivot.role', $role)->count(),
                ),
                HouseholdRole::cases(),
            ),
            planStatus: $planStatus,
        );
    }
}
