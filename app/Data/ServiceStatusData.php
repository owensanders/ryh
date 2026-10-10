<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ServiceStateEnum;
use Spatie\LaravelData\Data;

class ServiceStatusData extends Data
{
    public function __construct(
        public string $name,
        public string $description,
        public ServiceStateEnum $state,
        public string $stateLabel,
    ) {}

    public static function of(string $name, string $description, ServiceStateEnum $state): self
    {
        return new self($name, $description, $state, $state->label());
    }
}
