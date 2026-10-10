<?php

declare(strict_types=1);

namespace App\UseCases\Settings;

use App\Data\PushCategoryData;
use App\Enums\NotificationCategoryEnum;
use App\Models\User;

class ShowNotificationSettingsUseCase
{
    /** @return array{categories: array<int, PushCategoryData>, vapidPublicKey: string|null} */
    public function execute(User $user): array
    {
        return [
            'categories' => array_map(
                fn (NotificationCategoryEnum $category) => PushCategoryData::fromCategory($category, $user),
                NotificationCategoryEnum::cases(),
            ),
            // Null when VAPID keys aren't configured — the page then says push isn't available.
            'vapidPublicKey' => config('webpush.vapid.public_key') ?: null,
        ];
    }
}
