<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Http\Requests\ShoppingItemRequest;
use App\Http\Requests\ShoppingListRequest;
use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Traits\ResolvesHouseholdTrait;
use App\UseCases\Shopping\AddShoppingItemUseCase;
use App\UseCases\Shopping\CreateShoppingListUseCase;
use App\UseCases\Shopping\DeleteShoppingItemUseCase;
use App\UseCases\Shopping\DeleteShoppingListUseCase;
use App\UseCases\Shopping\GetShoppingScreenUseCase;
use App\UseCases\Shopping\RenameShoppingListUseCase;
use App\UseCases\Shopping\ToggleShoppingItemUseCase;
use App\UseCases\Shopping\UpdateShoppingItemUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShoppingListController extends Controller
{
    use ResolvesHouseholdTrait;

    public function __construct(private readonly ShoppingRepositoryInterface $shopping) {}

    public function index(Request $request, GetShoppingScreenUseCase $getShoppingScreen, ?string $slug = null): Response
    {
        $household = $this->household($request);

        $active = $slug !== null
            ? $this->shopping->findListBySlug($household, $slug)
            : null;

        abort_if($slug !== null && $active === null, 404);

        return Inertia::render('ShoppingLists', $getShoppingScreen->execute($household, $active));
    }

    public function store(ShoppingItemRequest $request, string $slug, AddShoppingItemUseCase $addItem): RedirectResponse
    {
        $list = $this->shopping->findListBySlug($this->household($request), $slug);

        abort_if($list === null, 404);

        $item = $addItem->execute($list, $request->validated('name'), $request->validated('quantity'));

        return back()->with('toast', "“{$item->name}” added to {$list->name}");
    }

    public function toggle(Request $request, ShoppingItem $item, ToggleShoppingItemUseCase $toggle): RedirectResponse
    {
        $this->assertItemOwned($request, $item);

        $toggle->execute($item);

        return back();
    }

    public function storeList(ShoppingListRequest $request, CreateShoppingListUseCase $create): RedirectResponse
    {
        $attributes = $request->listAttributes();
        $list = $create->execute($this->household($request), $attributes['name'], $attributes['colour']);

        return back()->with('toast', "“{$list->name}” added");
    }

    public function updateList(ShoppingListRequest $request, ShoppingList $list, RenameShoppingListUseCase $rename): RedirectResponse
    {
        $this->assertOwned($request, $list);

        $attributes = $request->listAttributes();
        $updated = $rename->execute($list, $attributes['name'], $attributes['colour']);

        return back()->with('toast', "“{$updated->name}” updated");
    }

    public function destroyList(Request $request, ShoppingList $list, DeleteShoppingListUseCase $delete): RedirectResponse
    {
        $this->assertOwned($request, $list);

        $name = $list->name;
        $delete->execute($list);

        // Not back(): the referer is the just-deleted list's own URL, which 404s.
        return redirect()->route('shopping.index')->with('toast', "“{$name}” deleted");
    }

    public function updateItem(ShoppingItemRequest $request, ShoppingItem $item, UpdateShoppingItemUseCase $update): RedirectResponse
    {
        $this->assertItemOwned($request, $item);

        $updated = $update->execute(
            $item,
            (string) $request->validated('name'),
            $request->validated('quantity'),
        );

        return back()->with('toast', "“{$updated->name}” updated");
    }

    public function destroyItem(Request $request, ShoppingItem $item, DeleteShoppingItemUseCase $delete): RedirectResponse
    {
        $this->assertItemOwned($request, $item);

        $name = $item->name;
        $delete->execute($item);

        return back()->with('toast', "“{$name}” deleted");
    }

    private function assertOwned(Request $request, ShoppingList $list): void
    {
        abort_if($list->household_id !== $this->household($request)->id, 404);
    }

    private function assertItemOwned(Request $request, ShoppingItem $item): void
    {
        abort_if($item->list?->household_id !== $this->household($request)->id, 404);
    }
}
