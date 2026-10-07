<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\LegalData;
use App\Data\PlanData;
use Inertia\Inertia;
use Inertia\Response;

/** The public Privacy, Terms and Support pages linked from the landing footer and registration. */
class LegalController extends Controller
{
    public function privacy(): Response
    {
        return Inertia::render('legal/Privacy', ['legal' => LegalData::current()]);
    }

    public function terms(): Response
    {
        return Inertia::render('legal/Terms', [
            'legal' => LegalData::current(),
            'plan' => PlanData::current(),
        ]);
    }

    public function cookies(): Response
    {
        return Inertia::render('legal/Cookies', [
            'legal' => LegalData::current(),
            'cookies' => [
                'sessionCookie' => config('session.cookie'),
                'sessionLifetimeMinutes' => (int) config('session.lifetime'),
                'expireOnClose' => (bool) config('session.expire_on_close'),
                // Laravel's "Remember me" cookie lasts 400 days (SessionGuard::$rememberDuration).
                'rememberDays' => 400,
            ],
        ]);
    }

    public function support(): Response
    {
        return Inertia::render('Support', [
            'legal' => LegalData::current(),
            'plan' => PlanData::current(),
        ]);
    }
}
