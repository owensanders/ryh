<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmPasswordRequest;
use App\Traits\ResolvesUserTrait;
use App\UseCases\Auth\ConfirmPasswordUseCase;
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
    public function store(ConfirmPasswordRequest $request, ConfirmPasswordUseCase $confirm): RedirectResponse
    {
        if (! $confirm->execute($this->user($request), $request->validated('password'))) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
