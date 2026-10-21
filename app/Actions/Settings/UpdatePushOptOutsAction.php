<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Enums\NotificationCategoryEnum;
use App\Models\User;

class UpdatePushOptOutsAction
{
    /** @param list<NotificationCategoryEnum> $optOuts */
    public function handle(User $user, array $optOuts): User
    {
        $user->update(['push_opt_outs' => array_map(fn (NotificationCategoryEnum $category) => $category->value, $optOuts)]);

        return $user->refresh();
    }
}
