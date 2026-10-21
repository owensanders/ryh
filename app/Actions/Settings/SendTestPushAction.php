<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\User;
use App\Notifications\Messages\PushMessage;
use App\Notifications\PushNotification;

/** Lets someone check a device really receives pushes, straight after turning them on. */
class SendTestPushAction
{
    public function handle(User $user): void
    {
        $user->notify(new PushNotification(new PushMessage(
            title: (string) config('app.name'),
            body: 'Notifications are working on this device.',
            url: route('notifications.edit'),
            tag: "test-{$user->id}",
        )));
    }
}
