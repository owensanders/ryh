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
        public ?string $emailVerifiedAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            avatar: null,
            emailVerifiedAt: $user->email_verified_at?->toJSON(),
            createdAt: $user->created_at->toJSON(),
            updatedAt: $user->updated_at->toJSON(),
        );
    }
}
