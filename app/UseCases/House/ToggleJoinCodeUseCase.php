<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Models\Household;

class ToggleJoinCodeUseCase
{
    public function __construct(private readonly HouseholdRepositoryInterface $households) {}

    public function execute(Household $household, bool $enabled): Household
    {
        return $this->households->setJoinCodeEnabled($household, $enabled);
    }
}
