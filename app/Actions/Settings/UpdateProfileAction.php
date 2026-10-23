<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Inputs\ProfileInput;
use App\Models\User;

class UpdateProfileAction
{
    public function handle(User $user, ProfileInput $input): User
    {
        $user->fill(['name' => $input->name, 'email' => $input->email]);

        // Changing the address makes the old verification meaningless. Set
        // directly: `email_verified_at` isn't fillable.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user->refresh();
    }
}
