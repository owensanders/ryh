<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Traits\ResolvesHouseholdTrait;
use App\Traits\ResolvesUserTrait;
use App\UseCases\Dashboard\GetDashboardUseCase;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    public function __invoke(Request $request, GetDashboardUseCase $getDashboard): Response
    {
        return Inertia::render('Dashboard', [
            'dashboard' => $getDashboard->execute(
                $this->household($request),
                str($this->user($request)->name)->before(' ')->toString(),
                CarbonImmutable::now(),
            ),
        ]);
    }
}
