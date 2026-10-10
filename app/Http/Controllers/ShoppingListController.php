<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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

    public function index(Request $request, GetShoppingScreenUseCase $getShoppingScreen, ?string $slug = null): Response
    {
        return Inertia::render('ShoppingLists', $getShoppingScreen->execute($this->household($request), $slug));
    }

    public function store(ShoppingItemRequest $request, string $slug, AddShoppingItemUseCase $addItem): RedirectResponse
    {
        $name = trim((string) $request->validated('name'));
        $list = $addItem->execute($this->household($request), $slug, $name, $request->validated('quantity'));

        return back()->with('toast', "“{$name}” added to {$list->name}");
    }

    public function toggle(ShoppingItem $item, ToggleShoppingItemUseCase $toggle): RedirectResponse
    {
        $toggle->execute($item);

        return back();
    }

    public function storeList(ShoppingListRequest $request, CreateShoppingListUseCase $create): RedirectResponse
    {
        $list = $create->execute($this->household($request), $request->toData());

        return back()->with('toast', "“{$list->name}” added");
    }

    public function updateList(ShoppingListRequest $request, ShoppingList $list, RenameShoppingListUseCase $rename): RedirectResponse
    {
        $updated = $rename->execute($list, $request->toData());

        return back()->with('toast', "“{$updated->name}” updated");
    }

    public function destroyList(ShoppingList $list, DeleteShoppingListUseCase $delete): RedirectResponse
    {
        $name = $list->name;
        $delete->execute($list);

        // Not back(): the referer is the just-deleted list's own URL, which 404s.
        return redirect()->route('shopping.index')->with('toast', "“{$name}” deleted");
    }

    public function updateItem(ShoppingItemRequest $request, ShoppingItem $item, UpdateShoppingItemUseCase $update): RedirectResponse
    {
        $updated = $update->execute(
            $item,
            (string) $request->validated('name'),
            $request->validated('quantity'),
        );

        return back()->with('toast', "“{$updated->name}” updated");
    }

    public function destroyItem(ShoppingItem $item, DeleteShoppingItemUseCase $delete): RedirectResponse
    {
        $name = $item->name;
        $delete->execute($item);

        return back()->with('toast', "“{$name}” deleted");
    }
}
