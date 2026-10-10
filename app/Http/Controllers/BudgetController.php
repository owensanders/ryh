<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BudgetCategoryRequest;
use App\Http\Requests\IndexBudgetRequest;
use App\Models\BudgetCategory;
use App\Traits\ResolvesHouseholdTrait;
use App\UseCases\Budget\CreateBudgetCategoryUseCase;
use App\UseCases\Budget\DeleteBudgetCategoryUseCase;
use App\UseCases\Budget\GetBudgetScreenUseCase;
use App\UseCases\Budget\UpdateBudgetCategoryUseCase;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function storeCategory(BudgetCategoryRequest $request, CreateBudgetCategoryUseCase $create): RedirectResponse
    {
        $month = $request->query('month');

        $category = $create->execute(
            $this->household($request),
            $month !== null ? CarbonImmutable::createFromFormat('!Y-m', (string) $month) ?? CarbonImmutable::now() : CarbonImmutable::now(),
            $request->categoryAttributes(),
        );

        return back()->with('toast', "{$category->label} added");
    }

    public function updateCategory(BudgetCategoryRequest $request, BudgetCategory $category, UpdateBudgetCategoryUseCase $update): RedirectResponse
    {
        $this->assertOwned($request, $category);

        $updated = $update->execute($category, $request->categoryAttributes());

        return back()->with('toast', "{$updated->label} updated");
    }

    public function destroyCategory(Request $request, BudgetCategory $category, DeleteBudgetCategoryUseCase $delete): RedirectResponse
    {
        $this->assertOwned($request, $category);

        $label = $category->label;
        $delete->execute($category);

        return back()->with('toast', "{$label} removed");
    }

    private function assertOwned(Request $request, BudgetCategory $category): void
    {
        abort_if($category->household_id !== $this->household($request)->id, 404);
    }
}
