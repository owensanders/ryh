<?php

declare(strict_types=1);

namespace App\Actions\Chores;

use App\Enums\ChoreStatusEnum;
use App\Models\Chore;

class MoveChoreAction
{
    public function handle(Chore $chore, ChoreStatusEnum $status): Chore
    {
        $chore->update(['status' => $status]);

        return $chore->refresh();
    }
}
