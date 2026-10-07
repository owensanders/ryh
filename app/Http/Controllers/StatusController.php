<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\UseCases\Status\GetPlatformStatusUseCase;
use Inertia\Inertia;
use Inertia\Response;

class StatusController extends Controller
{
    public function __invoke(GetPlatformStatusUseCase $status): Response
    {
        return Inertia::render('Status', ['status' => $status->execute()]);
    }
}
