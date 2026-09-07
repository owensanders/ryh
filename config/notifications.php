<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Chore Reminder Cooldown
    |--------------------------------------------------------------------------
    |
    | How long an Owner/Adult must wait between uses of the "Notify Chores"
    | button on the chores board, per household.
    |
    */

    'chore_reminder_cooldown_minutes' => (int) env('CHORE_REMINDER_COOLDOWN_MINUTES', 60),

];
