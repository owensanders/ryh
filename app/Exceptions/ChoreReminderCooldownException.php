<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/** "Notify Chores" was pressed again before the household's cooldown ran out. */
final class ChoreReminderCooldownException extends UserFacingException
{
    public function __construct(public readonly Carbon $availableAt)
    {
        parent::__construct('Reminders were sent recently — try again in a few minutes.');
    }

    public function errorKey(): string
    {
        return 'notify';
    }

    /** A toast on the page for the app; a plain 429 for anything else. */
    public function render(Request $request): Response
    {
        if (! $request->hasHeader('X-Inertia')) {
            return response($this->getMessage(), 429, ['Retry-After' => (string) max(1, (int) now()->diffInSeconds($this->availableAt))]);
        }

        return back()->with('toastError', $this->getMessage());
    }
}
