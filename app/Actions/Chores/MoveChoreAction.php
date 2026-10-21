<?php

declare(strict_types=1);

namespace App\Actions\Chores;

use App\Data\ChoreData;
use App\Enums\ChoreStatusEnum;
use App\Models\Chore;

class MoveChoreAction
{
    public function handle(Chore $chore, ChoreStatusEnum $status): ChoreData
    {
        $chore->update(['status' => $status]);

        return ChoreData::fromModel($chore->refresh());
    }
}
