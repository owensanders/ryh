<?php

declare(strict_types=1);

namespace App\Data\Input;

/** A chore as the add/edit form submits it, validated by ChoreRequest. */
final readonly class ChoreInputData
{
    public function __construct(
        public string $name,
        public ?int $assignedTo,
    ) {}
}
