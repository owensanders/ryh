<?php

declare(strict_types=1);

namespace App\Inputs;

use Spatie\LaravelData\Dto;

/** A new password from a reset link, as NewPasswordRequest validated it. */
final class PasswordResetInput extends Dto
{
    public function __construct(
        public readonly string $email,
        public readonly string $token,
        public readonly string $password,
    ) {}
}
