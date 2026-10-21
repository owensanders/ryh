<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\User;

class UpdateProfileAction
{
    /** @param  array<string, mixed>  $data  ProfileUpdateRequest's validated input: name, email. */
    public function handle(User $user, array $data): User
    {
        $user->fill(['name' => $data['name'], 'email' => $data['email']]);

        // Changing the address makes the old verification meaningless. Set
        // directly: `email_verified_at` isn't fillable.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user->refresh();
    }
}
