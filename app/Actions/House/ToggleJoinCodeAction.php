<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;
use App\Repositories\HouseholdRepository;

class ToggleJoinCodeAction
{
    public function __construct(private readonly HouseholdRepository $households) {}

    public function handle(Household $household, bool $enabled): Household
    {
        return $this->households->setJoinCodeEnabled($household, $enabled);
    }
}
