<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdatePasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use App\Traits\ResolvesUserTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    use ResolvesUserTrait;

    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(UpdatePasswordRequest $request, UpdatePasswordAction $updatePassword): RedirectResponse
    {
        $updatePassword->handle($this->user($request), $request->password());

        return back();
    }
}
