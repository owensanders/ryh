<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Auth\RecordLoginStreakAction;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordLoginStreakListener
{
    public function __construct(
        private readonly RecordLoginStreakAction $recordLoginStreak,
    ) {}

    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $this->recordLoginStreak->handle($user);
    }
}
