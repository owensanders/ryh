<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Models\Household;
use App\Repositories\HouseholdRepository;

class RegenerateJoinCodeUseCase
{
    public function __construct(private readonly HouseholdRepository $households) {}

    public function execute(Household $household): Household
    {
        return $this->households->setJoinCode($household, Household::generateJoinCode($household->name));
    }
}
