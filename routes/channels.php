<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('household.{householdId}', function (User $user, int $householdId) {
    return $user->households()->where('households.id', $householdId)->exists();
});
