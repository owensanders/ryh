<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\AuthUserData;
use App\Data\HouseholdAbilitiesData;
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
     * @return array{id: int, name: string, slug: string, can: HouseholdAbilitiesData}|null
     */
    private function household(Request $request): ?array
    {
        $user = $request->user();
        $household = CurrentHouseholdHelper::find($request);

        if ($household === null && $user instanceof User) {
            $membership = $user->lastMembership();
            $household = $membership !== null && ! $membership->pending ? $user->lastHousehold : null;
        }

        return $household !== null && $user instanceof User
            ? ['id' => $household->id, 'name' => $household->name, 'slug' => $household->slug, 'can' => HouseholdAbilitiesData::for($user, $household)]
            : null;
    }
}
