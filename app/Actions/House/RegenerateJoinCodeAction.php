<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;

class RegenerateJoinCodeAction
{
    public function handle(Household $household): Household
    {
        return tap($household)->update(['join_code' => Household::generateJoinCode($household->name)])->refresh();
    }
}
