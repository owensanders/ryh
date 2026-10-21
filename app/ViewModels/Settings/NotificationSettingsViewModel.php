<?php

declare(strict_types=1);

namespace App\ViewModels\Settings;

use App\Data\NotificationSettingsData;
use App\Data\PushCategoryData;
use App\Enums\NotificationCategoryEnum;
use App\Models\User;

class NotificationSettingsViewModel
{
    public function toData(User $user): NotificationSettingsData
    {
        return new NotificationSettingsData(
            categories: array_map(
                fn (NotificationCategoryEnum $category) => PushCategoryData::fromCategory($category, $user),
                NotificationCategoryEnum::cases(),
            ),
            // Null when VAPID keys aren't configured — the page then says push isn't available.
            vapidPublicKey: config('webpush.vapid.public_key') ?: null,
        );
    }
}
