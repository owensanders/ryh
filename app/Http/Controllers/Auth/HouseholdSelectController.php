<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\House\SwitchCurrentHouseholdAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\HouseholdSelectRequest;
use App\Traits\ResolvesUserTrait;
use App\ViewModels\House\HouseholdOptionsViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HouseholdSelectController extends Controller
{
    use ResolvesUserTrait;

    public function show(Request $request, HouseholdOptionsViewModel $getOptions): Response
    {
        return Inertia::render('auth/HouseholdSelect', [
            'households' => $getOptions->toData($this->user($request)),
            'currentHouseholdId' => $this->user($request)->last_household_id,
        ]);
    }

    public function store(HouseholdSelectRequest $request, SwitchCurrentHouseholdAction $switch): RedirectResponse
    {
        $switch->handle($this->user($request), (int) $request->validated('household_id'));

        return to_route('app');
    }
}
