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
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    use ResolvesHouseholdTrait;

    /** Only reads: a month is set up by the schedule, by setUpMonth() or by adding to it. */
    public function index(IndexBudgetRequest $request, BudgetViewModel $budget): Response
    {
        return Inertia::render('Budget', $budget->toData($this->household($request), $request->month()));
    }

    /** Bringing the recurring categories and income into a month before the schedule does. */
    public function setUpMonth(SetUpBudgetMonthRequest $request, SetUpBudgetMonthAction $setUp): RedirectResponse
    {
        $setUp->handle($this->household($request), $request->month());

        return back()->with('toast', 'Recurring items brought in');
    }

    public function storeCategory(BudgetCategoryRequest $request, CreateBudgetCategoryAction $create): RedirectResponse
    {
        $category = $create->handle($this->household($request), $request->month(), $request->toInput());

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
