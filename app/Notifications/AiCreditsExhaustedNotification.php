<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent on-demand to the admin alert address when the AI provider runs out of credit. */
class AiCreditsExhaustedNotification extends Notification
{
    public function __construct(private readonly string $provider) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hours = (int) config('meal_planner.pause_hours');

        return (new MailMessage)
            ->subject('HouseHub: AI meal planning paused — out of credit')
            ->line("The {$this->provider} API reported the account is out of credit, so AI meal planning has been paused for everyone.")
            ->line("Households see a friendly message and their weekly plan isn't used up. It retries automatically after {$hours} hours.")
            ->line('Top up the account, then run `php artisan ai:resume` to switch it back on straight away.');
    }
}
