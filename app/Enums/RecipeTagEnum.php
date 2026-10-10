<?php

declare(strict_types=1);

namespace App\Enums;

enum RecipeTagEnum: string
{
    case Healthy = 'healthy';
    case Vegetarian = 'vegetarian';
    case Vegan = 'vegan';
    case Quick = 'quick';
    case KidFriendly = 'kid-friendly';
    case ComfortFood = 'comfort-food';
    case Spicy = 'spicy';
    case BatchCook = 'batch-cook';
    case Budget = 'budget';
    case OnePot = 'one-pot';

    public function label(): string
    {
        return match ($this) {
            self::KidFriendly => 'Kid-friendly',
            self::ComfortFood => 'Comfort food',
            self::BatchCook => 'Batch cook',
            self::OnePot => 'One-pot',
            default => ucfirst($this->value),
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $tag) => ['value' => $tag->value, 'label' => $tag->label()],
            self::cases(),
        );
    }
}
