<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Data\ShoppingListData;
use App\Enums\PaletteEnum;
use App\Models\Household;

class CreateShoppingListUseCase
{
    public function __construct(private readonly ShoppingRepositoryInterface $shopping) {}

    public function execute(Household $household, string $name, PaletteEnum $colour): ShoppingListData
    {
        return ShoppingListData::fromModel($this->shopping->createList($household, $name, $colour), withItems: false);
    }
}
