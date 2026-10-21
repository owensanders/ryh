<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Auth\ResolveLandingAction;
use App\Traits\ResolvesUserTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * /app, and every household path from before they moved under /h/{household}
 * (old bell notifications, bookmarks, the installed app's start page), lands
 * on the same screen in the household they were last in.
 */
class AppLandingController extends Controller
{
    use ResolvesUserTrait;

    public function __invoke(Request $request, ResolveLandingAction $resolveLanding, string $path = 'dashboard'): RedirectResponse
    {
        $landing = $resolveLanding->handle($this->user($request));

        if ($landing->needsSetup) {
            return to_route('household.setup');
        }

        if ($landing->needsChoice || $landing->householdSlug === null) {
            return to_route('household.select');
        }

        $query = $request->getQueryString();

        return redirect("/h/{$landing->householdSlug}/".ltrim($path, '/').($query !== null ? "?{$query}" : ''));
    }
}
