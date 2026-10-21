<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Models\Household;
use App\Repositories\HouseholdRepository;

class ToggleJoinCodeUseCase
{
    public function __construct(private readonly HouseholdRepository $households) {}

    public function execute(Household $household, bool $enabled): Household
    {
        return $this->households->setJoinCodeEnabled($household, $enabled);
    }
}
