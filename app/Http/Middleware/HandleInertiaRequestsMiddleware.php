<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\AuthUserData;
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
        $household = $request->user()?->household;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() !== null ? AuthUserData::from($request->user()) : null,
            ],
            'household' => $household !== null
                ? ['id' => $household->id, 'name' => $household->name]
                : null,
            'flash' => [
                'toast' => fn () => $request->session()->get('toast'),
                'aiMeals' => fn () => $request->session()->get('aiMeals'),
                'libraryMeals' => fn () => $request->session()->get('libraryMeals'),
            ],
        ];
    }
}
