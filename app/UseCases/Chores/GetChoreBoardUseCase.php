<?php

declare(strict_types=1);

namespace App\UseCases\Chores;

use App\Contracts\Repositories\ChoreRepositoryInterface;
use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Data\ChoreColumnData;
use App\Data\ChoreData;
use App\Data\MemberData;
use App\Data\MemberScoreData;
use App\Enums\ChoreStatusEnum;
use App\Models\Household;
use App\Models\User;

class GetChoreBoardUseCase
{
    public function __construct(
        private readonly ChoreRepositoryInterface $chores,
        private readonly HouseholdRepositoryInterface $households,
    ) {}

    /** @return array{columns: array<int, ChoreColumnData>, scores: array<int, MemberScoreData>} */
    public function execute(Household $household): array
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

        return [
            'columns' => $columns,
            'scores' => $this->households->activeMembers($household)
                ->map(fn (User $member) => MemberScoreData::make(
                    member: MemberData::fromModel($member),
                    done: $chores->where('assigned_to', $member->id)->where('status', ChoreStatusEnum::Done)->count(),
                    total: $chores->where('assigned_to', $member->id)->count(),
                ))
                ->values()
                ->all(),
        ];
    }
}
