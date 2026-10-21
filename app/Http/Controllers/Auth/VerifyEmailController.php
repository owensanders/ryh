<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\VerifyEmailAction;
use App\Http\Controllers\Controller;
use App\Traits\ResolvesUserTrait;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    use ResolvesUserTrait;

    public function __invoke(EmailVerificationRequest $request, VerifyEmailAction $verify): RedirectResponse
    {
        $verify->handle($this->user($request));

        return redirect()->intended(route('app', absolute: false).'?verified=1');
    }
}
