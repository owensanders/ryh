<?php

declare(strict_types=1);

namespace App\Enums;

enum CookingConfidenceEnum: string
{
    case Beginner = 'beginner';
    case Comfortable = 'comfortable';
    case Adventurous = 'adventurous';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** How the agent should pitch recipe difficulty for this cook. */
    public function instruction(): string
    {
        return match ($this) {
            self::Beginner => 'The cook is a beginner: keep recipes simple, with few steps and familiar techniques.',
            self::Comfortable => 'The cook is comfortable in the kitchen: everyday recipes with the odd new technique are fine.',
            self::Adventurous => 'The cook is adventurous: more involved recipes and new techniques or cuisines are welcome.',
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $level) => ['value' => $level->value, 'label' => $level->label()],
            self::cases(),
        );
    }
}
