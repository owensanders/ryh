<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ChoreStatusEnum;
use App\Models\Chore;
use Spatie\LaravelData\Data;

class ChoreData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ChoreStatusEnum $status,
        public bool $done,
        public ?MemberData $assignee,
    ) {}

    public static function fromModel(Chore $chore): self
    {
        return new self(
            id: $chore->id,
            name: $chore->name,
            status: $chore->status,
            done: $chore->status === ChoreStatusEnum::Done,
            assignee: $chore->assignee !== null ? MemberData::fromModel($chore->assignee) : null,
        );
    }
}
