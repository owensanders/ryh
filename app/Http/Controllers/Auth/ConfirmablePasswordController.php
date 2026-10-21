<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ConfirmPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmPasswordRequest;
use App\Traits\ResolvesUserTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmablePasswordController extends Controller
{
    use ResolvesUserTrait;

    public function show(): Response
    {
        return Inertia::render('auth/ConfirmPassword');
    }

    /** @throws ValidationException */
    public function store(ConfirmPasswordRequest $request, ConfirmPasswordAction $confirm): RedirectResponse
    {
        if (! $confirm->handle($this->user($request), $request->validated('password'))) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        return redirect()->intended(route('app', absolute: false));
    }
}
