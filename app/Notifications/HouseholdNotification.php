<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Models\Household;
use App\Models\User;
use App\Notifications\Channels\QueuedBroadcastChannel;
use App\Notifications\Channels\QueuedWebPushChannel;
use App\Notifications\Messages\PushMessage;
use Illuminate\Notifications\Notification;

abstract class HouseholdNotification extends Notification
{
    /** The household it's about, passed in: the bell entry may be built outside any request for it. */
    public function __construct(protected readonly Household $household) {}

    /**
     * The bell (database, then a queued live update to the recipient alone)
     * always; a push too when the person has turned it on for a device and
     * hasn't opted out of this category.
     *
     * @param  User  $notifiable
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', QueuedBroadcastChannel::class];

        if ($notifiable->wantsPushFor($this->category()) && $notifiable->hasPushDevices()) {
            $channels[] = QueuedWebPushChannel::class;
        }

        return $channels;
    }

    /** Titled with the household's name, so someone in two households knows which one it's about. */
    public function toPushMessage(User $notifiable): PushMessage
    {
        $data = $this->toArray($notifiable);

        return new PushMessage(
            title: $this->household->name,
            body: $data['body'],
            url: $data['url'],
            tag: "{$this->notificationType()}-{$notifiable->id}",
        );
    }

    /** A screen in the household this is about, named outright rather than from a request. */
    protected function householdUrl(string $route): string
    {
        return route($route, ['household' => $this->household->slug]);
    }

    /** @return array<string, mixed> */
    abstract public function toArray(object $notifiable): array;

    abstract public function category(): NotificationCategoryEnum;

    abstract protected function notificationType(): string;
}
