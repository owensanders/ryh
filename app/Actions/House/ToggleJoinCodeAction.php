<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;

class ToggleJoinCodeAction
{
    public function handle(Household $household, bool $enabled): Household
    {
        return tap($household)->update(['join_code_enabled' => $enabled])->refresh();
    }
}
