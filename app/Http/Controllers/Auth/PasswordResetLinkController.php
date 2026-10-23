<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendPasswordResetLinkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(PasswordResetLinkRequest $request, SendPasswordResetLinkAction $sendLink): RedirectResponse
    {
        // The status is deliberately ignored — the response must not reveal whether
        // the address is registered.
        $sendLink->handle($request->email());

        return back()->with('status', __('A reset link will be sent if the account exists.'));
    }
}
