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
use Illuminate\Http\Request;

class IncomeSourceController extends Controller
{
    use ResolvesHouseholdTrait;

    public function store(IncomeSourceRequest $request, CreateIncomeSourceUseCase $create): RedirectResponse
    {
        $month = $request->query('month');

        $income = $create->execute(
            $this->household($request),
            $month !== null ? CarbonImmutable::createFromFormat('!Y-m', (string) $month) ?? CarbonImmutable::now() : CarbonImmutable::now(),
            $request->incomeAttributes(),
        );

        return back()->with('toast', "{$income->label} added");
    }

    public function update(IncomeSourceRequest $request, IncomeSource $income, UpdateIncomeSourceUseCase $update): RedirectResponse
    {
        $this->assertOwned($request, $income);

        $updated = $update->execute($income, $request->incomeAttributes());

        return back()->with('toast', "{$updated->label} updated");
    }

    public function destroy(Request $request, IncomeSource $income, DeleteIncomeSourceUseCase $delete): RedirectResponse
    {
        $this->assertOwned($request, $income);

        $label = $income->label;
        $delete->execute($income);

        return back()->with('toast', "{$label} removed");
    }

    private function assertOwned(Request $request, IncomeSource $income): void
    {
        abort_if($income->household_id !== $this->household($request)->id, 404);
    }
}
