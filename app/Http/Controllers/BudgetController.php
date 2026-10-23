<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Budget\CreateBudgetCategoryAction;
use App\Actions\Budget\DeleteBudgetCategoryAction;
use App\Actions\Budget\SetUpBudgetMonthAction;
use App\Actions\Budget\UpdateBudgetCategoryAction;
use App\Http\Requests\BudgetCategoryRequest;
use App\Http\Requests\IndexBudgetRequest;
use App\Http\Requests\SetUpBudgetMonthRequest;
use App\Models\BudgetCategory;
use App\Traits\ResolvesHouseholdTrait;
use App\ViewModels\Budget\BudgetViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    use ResolvesHouseholdTrait;

    /** Only reads: a month is set up by the schedule, by setUpMonth() or by adding to it. */
    public function index(IndexBudgetRequest $request, BudgetViewModel $budget): Response
    {
        $month = $request->validated('month');
        $household = $this->household($request);
        // `!` resets the unspecified day/time — without it PHP fills the day from
        // today, so on the 31st "2026-09" would roll over into October.
        $first = ($month !== null ? CarbonImmutable::createFromFormat('!Y-m', $month) ?? $household->now() : $household->now())->startOfMonth();

        return Inertia::render('Budget', $budget->toData($household, $first));
    }

    /** Bringing the recurring categories and income into a month before the schedule does. */
    public function setUpMonth(SetUpBudgetMonthRequest $request, SetUpBudgetMonthAction $setUp): RedirectResponse
    {
        $setUp->handle(
            $this->household($request),
            CarbonImmutable::createFromFormat('!Y-m', (string) $request->validated('month')) ?? $this->household($request)->now(),
        );

        return back()->with('toast', 'Recurring items brought in');
    }

    public function storeCategory(BudgetCategoryRequest $request, CreateBudgetCategoryAction $create): RedirectResponse
    {
        $month = $request->validated('month');

        $category = $create->handle(
            $this->household($request),
            $month !== null ? CarbonImmutable::createFromFormat('!Y-m', (string) $month) ?? $this->household($request)->now() : $this->household($request)->now(),
            $request->toInput(),
        );

        return back()->with('toast', "{$category->label} added");
    }

    public function updateCategory(BudgetCategoryRequest $request, BudgetCategory $category, UpdateBudgetCategoryAction $update): RedirectResponse
    {
        $updated = $update->handle($category, $request->toInput());

        return back()->with('toast', "{$updated->label} updated");
    }

    public function destroyCategory(BudgetCategory $category, DeleteBudgetCategoryAction $delete): RedirectResponse
    {
        $label = $category->label;
        $delete->handle($category);

        return back()->with('toast', "{$label} removed");
    }
}
