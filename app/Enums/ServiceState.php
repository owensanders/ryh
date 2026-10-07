<?php

declare(strict_types=1);

namespace App\Enums;

/** How one part of the platform is doing, as the public status page shows it. */
enum ServiceState: string
{
    case Operational = 'operational';
    case Degraded = 'degraded';
    case Down = 'down';

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'Operational',
            self::Degraded => 'Degraded',
            self::Down => 'Down',
        };
    }

    /** Higher is worse — the overall state is the worst component's. */
    public function severity(): int
    {
        return match ($this) {
            self::Operational => 0,
            self::Degraded => 1,
            self::Down => 2,
        };
    }
}
