<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RyhSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
 * Test-only setup for the Playwright suite, loaded only when APP_ENV=e2e
 * (bootstrap/app.php). Each test makes its own household here, so tests share
 * nothing and can run in parallel (e2e/support/fixtures.ts).
 */

// A fresh Parker household with its demo data; its members by role.
Route::post('/_e2e/households', function (RyhSeeder $seeder): JsonResponse {
    // Unguarded, as `db:seed` runs seeders: they set columns forms can't (email_verified_at).
    $household = Model::unguarded(fn () => $seeder->household(Str::lower(Str::random(10))));

    $members = $household->activeMembers()->get()->mapWithKeys(fn (User $member) => [
        $member->membershipIn($household->id)?->role->value => ['id' => $member->id, 'email' => $member->email, 'name' => $member->name],
    ]);

    return response()->json(['slug' => $household->slug, 'name' => $household->name, 'members' => $members]);
})->withoutMiddleware(ValidateCsrfToken::class);

// Signs the browser in as a user, skipping the login form; `to` is where it lands.
Route::get('/_e2e/login/{user}', function (Request $request, User $user): RedirectResponse {
    Auth::login($user);
    $request->session()->regenerate();

    return redirect($request->string('to', '/app')->toString());
});
