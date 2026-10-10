<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\InviteJoinOutcomeEnum;
use Spatie\LaravelData\Data;

class InviteJoinResultData extends Data
{
    public function __construct(
        public InviteJoinOutcomeEnum $outcome,
        public ?string $householdName,
    ) {}
}
