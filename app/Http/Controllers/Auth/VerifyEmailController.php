<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesUserTrait;
use App\UseCases\Auth\VerifyEmailUseCase;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    use ResolvesUserTrait;

    public function __invoke(EmailVerificationRequest $request, VerifyEmailUseCase $verify): RedirectResponse
    {
        $verify->execute($this->user($request));

        return redirect()->intended(route('app', absolute: false).'?verified=1');
    }
}
