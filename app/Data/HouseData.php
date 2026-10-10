<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class HouseData extends Data
{
    /**
     * @param  array<int, StatData>  $houseStats
     * @param  array<int, array{value: string, label: string}>  $roleOptions
     * @param  array<int, HouseMemberData>  $members
     * @param  array<int, RoleSummaryData>  $roles
     */
    public function __construct(
        public string $houseName,
        public string $houseCreated,
        public string $joinCode,
        public bool $joinCodeEnabled,
        public array $houseStats,
        public int $memberCount,
        public int $maxMembers,
        public array $roleOptions,
        public array $members,
        public array $roles,
        public PlanStatusData $planStatus,
    ) {}
}
