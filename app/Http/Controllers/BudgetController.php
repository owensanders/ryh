<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Budget\CreateBudgetCategoryAction;
use App\Actions\Budget\DeleteBudgetCategoryAction;
use App\Actions\Budget\UpdateBudgetCategoryAction;
use App\Http\Requests\BudgetCategoryRequest;
use App\Http\Requests\IndexBudgetRequest;
use App\Models\BudgetCategory;
use App\Traits\ResolvesHouseholdTrait;
use App\UseCases\Budget\GetBudgetScreenUseCase;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    use ResolvesHouseholdTrait;

    public function index(IndexBudgetRequest $request, GetBudgetScreenUseCase $getBudgetScreen): Response
    {
        $month = $request->validated('month');

        return Inertia::render('Budget', $getBudgetScreen->execute(
            $this->household($request),
            // `!` resets the unspecified day/time — without it PHP fills the day from
            // today, so on the 31st "2026-09" would roll over into October.
            $month !== null ? CarbonImmutable::createFromFormat('!Y-m', $month) ?? CarbonImmutable::now() : CarbonImmutable::now(),
        ));
    }

    public function storeCategory(BudgetCategoryRequest $request, CreateBudgetCategoryAction $create): RedirectResponse
    {
        $month = $request->query('month');

        $category = $create->handle(
            $this->household($request),
            $month !== null ? CarbonImmutable::createFromFormat('!Y-m', (string) $month) ?? CarbonImmutable::now() : CarbonImmutable::now(),
            $request->toData(),
        );

        return back()->with('toast', "{$category->label} added");
    }

    public function updateCategory(BudgetCategoryRequest $request, BudgetCategory $category, UpdateBudgetCategoryAction $update): RedirectResponse
    {
        $updated = $update->handle($category, $request->toData());

        return back()->with('toast', "{$updated->label} updated");
    }

    public function destroyCategory(BudgetCategory $category, DeleteBudgetCategoryAction $delete): RedirectResponse
    {
        $label = $category->label;
        $delete->handle($category);

        return back()->with('toast', "{$label} removed");
    }
}
