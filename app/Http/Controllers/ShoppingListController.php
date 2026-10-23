<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Shopping\AddShoppingItemAction;
use App\Actions\Shopping\CreateShoppingListAction;
use App\Actions\Shopping\DeleteShoppingItemAction;
use App\Actions\Shopping\DeleteShoppingListAction;
use App\Actions\Shopping\RenameShoppingListAction;
use App\Actions\Shopping\ToggleShoppingItemAction;
use App\Actions\Shopping\UpdateShoppingItemAction;
use App\Http\Requests\ShoppingItemRequest;
use App\Http\Requests\ShoppingListRequest;
use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Traits\ResolvesHouseholdTrait;
use App\Traits\ResolvesUserTrait;
use App\ViewModels\Shopping\ShoppingViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShoppingListController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    public function index(Request $request, ShoppingViewModel $getShoppingScreen, ?string $slug = null): Response
    {
        return Inertia::render('ShoppingLists', $getShoppingScreen->toData($this->household($request), $this->user($request), $slug));
    }

    public function store(ShoppingItemRequest $request, string $slug, AddShoppingItemAction $addItem): RedirectResponse
    {
        $name = (string) $request->validated('name');
        $list = $addItem->handle($this->household($request), $slug, $name, $request->validated('quantity'));

        return back()->with('toast', "“{$name}” added to {$list->name}");
    }

    public function toggle(ShoppingItem $item, ToggleShoppingItemAction $toggle): RedirectResponse
    {
        $toggle->handle($item);

        return back();
    }

    public function storeList(ShoppingListRequest $request, CreateShoppingListAction $create): RedirectResponse
    {
        $list = $create->handle($this->household($request), $request->toInput());

        return back()->with('toast', "“{$list->name}” added");
    }

    public function updateList(ShoppingListRequest $request, ShoppingList $list, RenameShoppingListAction $rename): RedirectResponse
    {
        $updated = $rename->handle($list, $request->toInput());

        return back()->with('toast', "“{$updated->name}” updated");
    }

    public function destroyList(ShoppingList $list, DeleteShoppingListAction $delete): RedirectResponse
    {
        $name = $list->name;
        $delete->handle($list);

        // Not back(): the referer is the just-deleted list's own URL, which 404s.
        return redirect()->route('shopping.index')->with('toast', "“{$name}” deleted");
    }

    public function updateItem(ShoppingItemRequest $request, ShoppingItem $item, UpdateShoppingItemAction $update): RedirectResponse
    {
        $updated = $update->handle(
            $item,
            (string) $request->validated('name'),
            $request->validated('quantity'),
        );

        return back()->with('toast', "“{$updated->name}” updated");
    }

    public function destroyItem(ShoppingItem $item, DeleteShoppingItemAction $delete): RedirectResponse
    {
        $name = $item->name;
        $delete->handle($item);

        return back()->with('toast', "“{$name}” deleted");
    }
}
