<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IncomeSourceRequest;
use App\Models\IncomeSource;
use App\Traits\ResolvesHouseholdTrait;
use App\UseCases\Budget\CreateIncomeSourceUseCase;
use App\UseCases\Budget\DeleteIncomeSourceUseCase;
use App\UseCases\Budget\UpdateIncomeSourceUseCase;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

class IncomeSourceController extends Controller
{
    use ResolvesHouseholdTrait;

    public function store(IncomeSourceRequest $request, CreateIncomeSourceUseCase $create): RedirectResponse
    {
        $month = $request->query('month');

        $income = $create->execute(
            $this->household($request),
            $month !== null ? CarbonImmutable::createFromFormat('!Y-m', (string) $month) ?? CarbonImmutable::now() : CarbonImmutable::now(),
            $request->toData(),
        );

        return back()->with('toast', "{$income->label} added");
    }

    public function update(IncomeSourceRequest $request, IncomeSource $income, UpdateIncomeSourceUseCase $update): RedirectResponse
    {
        $updated = $update->execute($income, $request->toData());

        return back()->with('toast', "{$updated->label} updated");
    }

    public function destroy(IncomeSource $income, DeleteIncomeSourceUseCase $delete): RedirectResponse
    {
        $label = $income->label;
        $delete->execute($income);

        return back()->with('toast', "{$label} removed");
    }
}
