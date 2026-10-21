<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendEmailVerificationAction;
use App\Http\Controllers\Controller;
use App\Traits\ResolvesUserTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    use ResolvesUserTrait;

    public function store(Request $request, SendEmailVerificationAction $sendVerification): RedirectResponse
    {
        if (! $sendVerification->handle($this->user($request))) {
            return redirect()->intended(route('app', absolute: false));
        }

        return back()->with('status', 'verification-link-sent');
    }
}
