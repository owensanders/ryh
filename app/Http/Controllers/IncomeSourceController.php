<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Budget\CreateIncomeSourceAction;
use App\Actions\Budget\DeleteIncomeSourceAction;
use App\Actions\Budget\UpdateIncomeSourceAction;
use App\Http\Requests\IncomeSourceRequest;
use App\Models\IncomeSource;
use App\Traits\ResolvesHouseholdTrait;
use Illuminate\Http\RedirectResponse;

class IncomeSourceController extends Controller
{
    use ResolvesHouseholdTrait;

    public function store(IncomeSourceRequest $request, CreateIncomeSourceAction $create): RedirectResponse
    {
        $income = $create->handle($this->household($request), $request->month(), $request->toInput());

        return back()->with('toast', "{$income->label} added");
    }

    public function update(IncomeSourceRequest $request, IncomeSource $income, UpdateIncomeSourceAction $update): RedirectResponse
    {
        $updated = $update->handle($income, $request->toInput());

        return back()->with('toast', "{$updated->label} updated");
    }

    public function destroy(IncomeSource $income, DeleteIncomeSourceAction $delete): RedirectResponse
    {
        $label = $income->label;
        $delete->handle($income);

        return back()->with('toast', "{$label} removed");
    }
}
