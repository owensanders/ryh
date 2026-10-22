<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\NotificationCategoryEnum;
use App\Models\User;
use Spatie\LaravelData\Data;

/** One "What to send" toggle on Settings → Notifications. */
class PushCategoryData extends Data
{
    public function __construct(
        public NotificationCategoryEnum $value,
        public string $label,
        public string $description,
        public bool $enabled,
    ) {}

    public static function fromCategory(NotificationCategoryEnum $category, User $user): self
    {
        return new self($category, $category->label(), $category->description(), $user->wantsPushFor($category));
    }
}
