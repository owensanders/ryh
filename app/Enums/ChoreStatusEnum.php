<?php

declare(strict_types=1);

namespace App\Enums;

/** The two columns of the chore board. */
enum ChoreStatusEnum: string
{
    case Today = 'today';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Today => "Today's tasks",
            self::Done => 'Completed',
        };
    }
}
