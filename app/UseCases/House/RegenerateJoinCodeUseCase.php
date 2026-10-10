<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Models\Household;

class RegenerateJoinCodeUseCase
{
    public function __construct(private readonly HouseholdRepositoryInterface $households) {}

    public function execute(Household $household): Household
    {
        return $this->households->setJoinCode($household, Household::generateJoinCode($household->name));
    }
}
