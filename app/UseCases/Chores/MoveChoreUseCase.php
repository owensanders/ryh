<?php

declare(strict_types=1);

namespace App\UseCases\Chores;

use App\Contracts\Repositories\ChoreRepositoryInterface;
use App\Data\ChoreData;
use App\Enums\ChoreStatusEnum;
use App\Models\Chore;

class MoveChoreUseCase
{
    public function __construct(private readonly ChoreRepositoryInterface $chores) {}

    public function execute(Chore $chore, ChoreStatusEnum $status): ChoreData
    {
        return ChoreData::fromModel($this->chores->moveTo($chore, $status));
    }
}
