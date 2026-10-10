<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Data\Input\ShoppingListInputData;
use App\Data\ShoppingListData;
use App\Models\Household;

class CreateShoppingListUseCase
{
    public function __construct(private readonly ShoppingRepositoryInterface $shopping) {}

    public function execute(Household $household, ShoppingListInputData $input): ShoppingListData
    {
        return ShoppingListData::fromModel($this->shopping->createList($household, $input->name, $input->colour), withItems: false);
    }
}
