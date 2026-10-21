<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Data\ShoppingListData;
use App\Models\Household;
use App\Repositories\ShoppingRepository;

class GetShoppingListOptionsUseCase
{
    public function __construct(private readonly ShoppingRepository $shopping) {}

    /** @return array<int, ShoppingListData> */
    public function execute(Household $household): array
    {
        return $this->shopping->listsFor($household)
            ->map(fn ($list) => ShoppingListData::fromModel($list, withItems: false))
            ->values()
            ->all();
    }
}
