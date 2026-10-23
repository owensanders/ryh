<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\DeleteAccountAction;
use App\Actions\Settings\UpdateProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\DeleteAccountRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Traits\ResolvesUserTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    use ResolvesUserTrait;

    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $request, UpdateProfileAction $updateProfile): RedirectResponse
    {
        $updateProfile->handle($this->user($request), $request->toInput());

        return to_route('profile.edit');
    }

    public function destroy(DeleteAccountRequest $request, DeleteAccountAction $deleteAccount): RedirectResponse
    {
        $deleteAccount->handle($this->user($request), $request->boolean('confirm_household_deletion'));

        return redirect('/');
    }
}
