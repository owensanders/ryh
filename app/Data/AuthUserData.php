<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;

class AuthUserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $avatar,
        public ?string $email_verified_at,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            avatar: null,
            email_verified_at: $user->email_verified_at?->toJSON(),
            created_at: $user->created_at->toJSON(),
            updated_at: $user->updated_at->toJSON(),
        );
    }
}
