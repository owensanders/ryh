<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\UseCases\Auth\RecordLoginStreakUseCase;
use Illuminate\Auth\Events\Login;

class RecordLoginStreakListener
{
    public function __construct(
        private readonly RecordLoginStreakUseCase $recordLoginStreak,
    ) {}

    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $this->recordLoginStreak->execute($user);
    }
}
