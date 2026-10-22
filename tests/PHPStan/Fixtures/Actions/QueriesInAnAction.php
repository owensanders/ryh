<?php

declare(strict_types=1);

namespace Tests\PHPStan\Fixtures\Actions;

use App\Models\Chore;
use App\Models\Household;

/** Actions may query: QueryBoundaryRule reports nothing here. */
class QueriesInAnAction
{
    public function handle(Household $household): void
    {
        $household->chores()->where('name', 'Bins')->get();
        Chore::query()->delete();
    }
}
