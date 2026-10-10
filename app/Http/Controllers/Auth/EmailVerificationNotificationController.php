<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesUserTrait;
use App\UseCases\Auth\SendEmailVerificationUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    use ResolvesUserTrait;

    public function store(Request $request, SendEmailVerificationUseCase $sendVerification): RedirectResponse
    {
        if (! $sendVerification->execute($this->user($request))) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->with('status', 'verification-link-sent');
    }
}
