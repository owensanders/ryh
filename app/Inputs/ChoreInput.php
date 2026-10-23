<?php

declare(strict_types=1);

namespace App\Inputs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Dto;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** A chore as ChoreRequest validated it. */
#[MapInputName(SnakeCaseMapper::class)]
final class ChoreInput extends Dto
{
    public function __construct(
        public readonly string $name,
        /** Null for nobody. */
        public readonly ?int $assignedTo,
    ) {}
}
