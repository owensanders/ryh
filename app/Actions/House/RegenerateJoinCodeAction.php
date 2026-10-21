<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;
use App\Repositories\HouseholdRepository;

class RegenerateJoinCodeAction
{
    public function __construct(private readonly HouseholdRepository $households) {}

    public function handle(Household $household): Household
    {
        return $this->households->setJoinCode($household, Household::generateJoinCode($household->name));
    }
}
