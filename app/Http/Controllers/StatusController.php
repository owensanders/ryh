<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ViewModels\Status\PlatformStatusViewModel;
use Inertia\Inertia;
use Inertia\Response;

class StatusController extends Controller
{
    public function __invoke(PlatformStatusViewModel $status): Response
    {
        return Inertia::render('Status', ['status' => $status->toData()]);
    }
}
