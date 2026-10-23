<?php

declare(strict_types=1);

namespace App\Inputs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Dto;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** Leaving a household, as LeaveHouseholdRequest validated it. */
#[MapInputName(SnakeCaseMapper::class)]
final class LeaveHouseholdInput extends Dto
{
    public function __construct(
        public readonly int $householdId,
        /** A sole owner leaving deletes the household; they have to say so. */
        public readonly bool $confirmHouseholdDeletion = false,
    ) {}
}
