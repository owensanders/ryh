<?php

declare(strict_types=1);

namespace App\UseCases\Chores;

use App\Data\ChoreData;
use App\Enums\ChoreStatusEnum;
use App\Models\Chore;
use App\Repositories\ChoreRepository;

class MoveChoreUseCase
{
    public function __construct(private readonly ChoreRepository $chores) {}

    public function execute(Chore $chore, ChoreStatusEnum $status): ChoreData
    {
        return ChoreData::fromModel($this->chores->moveTo($chore, $status));
    }
}
