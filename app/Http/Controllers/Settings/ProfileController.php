<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\DeleteAccountRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Traits\ResolvesUserTrait;
use App\UseCases\Settings\DeleteAccountUseCase;
use App\UseCases\Settings\UpdateProfileUseCase;
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

    public function update(ProfileUpdateRequest $request, UpdateProfileUseCase $updateProfile): RedirectResponse
    {
        $updateProfile->execute($this->user($request), $request->validated());

        return to_route('profile.edit');
    }

    public function destroy(DeleteAccountRequest $request, DeleteAccountUseCase $deleteAccount): RedirectResponse
    {
        $deleteAccount->execute($this->user($request), $request->boolean('confirm_household_deletion'));

        return redirect('/');
    }
}
