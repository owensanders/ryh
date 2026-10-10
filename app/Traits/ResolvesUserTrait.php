<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

trait ResolvesUserTrait
{
    /**
     * The signed-in user. These actions sit behind the `auth` middleware, so a
     * missing user means it was bypassed: answer as `auth` would (sign in)
     * rather than carry on with null.
     *
     * @throws AuthenticationException
     */
    protected function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }
}
