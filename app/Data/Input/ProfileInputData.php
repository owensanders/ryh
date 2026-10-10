<?php

declare(strict_types=1);

namespace App\Data\Input;

/** Someone's own name and email, validated by ProfileUpdateRequest. */
final readonly class ProfileInputData
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}
