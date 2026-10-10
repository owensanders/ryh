<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ServiceStateEnum;
use Spatie\LaravelData\Data;

class PlatformStatusData extends Data
{
    /** @param  array<int, ServiceStatusData>  $services */
    public function __construct(
        public ServiceStateEnum $overall,
        public array $services,
        /** ISO 8601, so the page can show it in the visitor's own time. */
        public string $checkedAt,
    ) {}
}
