<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\AuthUserData;
use App\Models\User;
use App\Support\CurrentHouseholdHelper;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequestsMiddleware extends Middleware
{
    /**
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /** @see https://inertiajs.com/asset-versioning */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() !== null ? AuthUserData::from($request->user()) : null,
            ],
            // Lazy: this runs before the route's middleware has resolved the household.
            'household' => fn () => $this->household($request),
            'flash' => [
                'toast' => fn () => $request->session()->get('toast'),
                'toastError' => fn () => $request->session()->get('toastError'),
                'aiMeals' => fn () => $request->session()->get('aiMeals'),
                'libraryMeals' => fn () => $request->session()->get('libraryMeals'),
            ],
        ];
    }

    /**
     * The household in the URL; or, on pages outside one (settings), the one
     * they were last in, which the shell's links lead back to. The frontend
     * keeps Ziggy's {household} default in step with the slug.
     *
     * @return array{id: int, name: string, slug: string, role: string|null}|null
     */
    private function household(Request $request): ?array
    {
        $household = CurrentHouseholdHelper::find($request);
        $role = CurrentHouseholdHelper::role($request);

        if ($household === null && $request->user() instanceof User) {
            $membership = $request->user()->lastMembership();
            $household = $membership !== null && ! $membership->pending ? $request->user()->lastHousehold : null;
            $role = $household !== null ? $membership->role : null;
        }

        return $household !== null
            ? ['id' => $household->id, 'name' => $household->name, 'slug' => $household->slug, 'role' => $role?->value]
            : null;
    }
}
