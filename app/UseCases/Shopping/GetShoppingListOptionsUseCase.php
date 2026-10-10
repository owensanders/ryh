<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Data\ShoppingListData;
use App\Models\Household;

class GetShoppingListOptionsUseCase
{
    public function __construct(private readonly ShoppingRepositoryInterface $shopping) {}

    /** @return array<int, ShoppingListData> */
    public function execute(Household $household): array
    {
        return $this->shopping->listsFor($household)
            ->map(fn ($list) => ShoppingListData::fromModel($list, withItems: false))
            ->values()
            ->all();
    }
}
