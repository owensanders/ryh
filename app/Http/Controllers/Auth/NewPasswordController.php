<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResetPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\NewPasswordRequest;
use App\Http\Requests\Auth\ShowNewPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    public function create(ShowNewPasswordRequest $request, string $token): Response
    {
        return Inertia::render('auth/ResetPassword', ['email' => $request->email(), 'token' => $token]);
    }

    /** @throws ValidationException */
    public function store(NewPasswordRequest $request, ResetPasswordAction $reset): RedirectResponse
    {
        $status = $reset->handle($request->toInput());

        if ($status === Password::PasswordReset) {
            return to_route('login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
