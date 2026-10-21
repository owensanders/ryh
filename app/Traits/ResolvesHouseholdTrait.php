<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Household;
use App\Support\CurrentHouseholdHelper;
use Illuminate\Http\Request;

trait ResolvesHouseholdTrait
{
    /** The household in this request's /h/{household} URL, already checked by ResolveHouseholdMiddleware. */
    protected function household(Request $request): Household
    {
        return CurrentHouseholdHelper::household($request);
    }
}
