<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ServiceState;
use Spatie\LaravelData\Data;

class ServiceStatusData extends Data
{
    public function __construct(
        public string $name,
        public string $description,
        public ServiceState $state,
        public string $stateLabel,
    ) {}

    public static function of(string $name, string $description, ServiceState $state): self
    {
        return new self($name, $description, $state, $state->label());
    }
}
