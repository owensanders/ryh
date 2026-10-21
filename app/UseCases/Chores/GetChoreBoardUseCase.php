<?php

declare(strict_types=1);

namespace App\UseCases\Chores;

use App\Data\ChoreBoardScreenData;
use App\Data\ChoreColumnData;
use App\Data\ChoreData;
use App\Data\MemberData;
use App\Data\MemberScoreData;
use App\Enums\ChoreStatusEnum;
use App\Models\Household;
use App\Models\User;
use App\Repositories\ChoreRepository;
use App\Repositories\MembershipRepository;

class GetChoreBoardUseCase
{
    public function __construct(
        private readonly ChoreRepository $chores,
        private readonly MembershipRepository $memberships,
    ) {}

    public function execute(Household $household): ChoreBoardScreenData
    {
        $chores = $this->chores->allFor($household);

        $columns = array_map(
            fn (ChoreStatusEnum $status) => new ChoreColumnData(
                status: $status->value,
                title: $status->label(),
                count: $chores->where('status', $status)->count(),
                items: $chores->where('status', $status)->map(ChoreData::fromModel(...))->values()->all(),
            ),
            ChoreStatusEnum::cases(),
        );

        return new ChoreBoardScreenData(
            columns: $columns,
            scores: $this->memberships->activeMembers($household)
                ->map(fn (User $member) => MemberScoreData::make(
                    member: MemberData::fromModel($member),
                    done: $chores->where('assigned_to', $member->id)->where('status', ChoreStatusEnum::Done)->count(),
                    total: $chores->where('assigned_to', $member->id)->count(),
                ))
                ->values()
                ->all(),
            members: $this->memberships->members($household)->map(MemberData::fromModel(...))->values()->all(),
            notifyChoresAvailableAt: $household->choreRemindersAvailableAt()?->toJSON(),
        );
    }
}
