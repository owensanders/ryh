<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ChoreStatusEnum;
use Spatie\LaravelData\Data;

class ChoreColumnData extends Data
{
    /** @param array<int, ChoreData> $items */
    public function __construct(
        public ChoreStatusEnum $status,
        public string $title,
        public int $count,
        public array $items,
    ) {}
}
