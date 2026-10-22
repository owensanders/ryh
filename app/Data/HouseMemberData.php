<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PendingReasonEnum;
use App\Models\User;
use LogicException;
use Spatie\LaravelData\Data;

class HouseMemberData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $initials,
        public string $colour,
        public string $email,
        public string $role,
        public string $roleLabel,
        public string $activity,
        public bool $you,
        public bool $pending,
        public ?PendingReasonEnum $pendingReason,
    ) {}

    public static function fromModel(User $user, int $viewerId): self
    {
        $membership = $user->pivot ?? throw new LogicException('HouseMemberData needs a user loaded through a household, with its membership.');

        return new self(
            id: $user->id,
            name: $user->name,
            initials: $user->initials ?? mb_strtoupper(mb_substr($user->name, 0, 2)),
            colour: $user->colour->cssVar(),
            email: $user->email,
            role: $membership->role->value,
            roleLabel: $membership->role->label(),
            activity: $user->status_line ?? '',
            you: $user->id === $viewerId,
            pending: $membership->pending,
            pendingReason: $membership->pending_reason,
        );
    }
}
