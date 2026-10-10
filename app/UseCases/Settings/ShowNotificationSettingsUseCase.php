<?php

declare(strict_types=1);

namespace App\UseCases\Settings;

use App\Data\PushCategoryData;
use App\Enums\NotificationCategory;
use App\Models\User;

class ShowNotificationSettingsUseCase
{
    /** @return array{categories: array<int, PushCategoryData>, vapidPublicKey: string|null} */
    public function execute(User $user): array
    {
        return [
            'categories' => array_map(
                fn (NotificationCategory $category) => PushCategoryData::fromCategory($category, $user),
                NotificationCategory::cases(),
            ),
            // Null when VAPID keys aren't configured — the page then says push isn't available.
            'vapidPublicKey' => config('webpush.vapid.public_key') ?: null,
        ];
    }
}
