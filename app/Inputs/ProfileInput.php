<?php

declare(strict_types=1);

namespace App\Inputs;

use Spatie\LaravelData\Dto;

/** A user's profile as ProfileUpdateRequest validated it. */
final class ProfileInput extends Dto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
