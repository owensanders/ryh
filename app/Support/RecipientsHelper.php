<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/** Who hears about a change: everyone involved, once each, never the person who made it. */
final class RecipientsHelper
{
    /**
     * @param  iterable<User|null>  $involved  e.g. a chore's assignee and creator; nulls are skipped.
     *
     * @return list<User>
     */
    public static function except(User $actor, iterable $involved): array
    {
        $recipients = [];
        $seen = [$actor->id => true];

        foreach ($involved as $person) {
            if ($person !== null && ! isset($seen[$person->id])) {
                $recipients[] = $person;
                $seen[$person->id] = true;
            }
        }

        return $recipients;
    }
}
