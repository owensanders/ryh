<?php

declare(strict_types=1);

namespace App\Inputs;

use Spatie\LaravelData\Dto;

/** A new account as RegisterRequest validated it. */
final class RegistrationInput extends Dto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}
}
