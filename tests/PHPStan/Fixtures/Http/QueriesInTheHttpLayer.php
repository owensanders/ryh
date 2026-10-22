<?php

declare(strict_types=1);

namespace Tests\PHPStan\Fixtures\Http;

use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Breaks QueryBoundaryRule on purpose; the line numbers are asserted in QueryBoundaryRuleTest. */
class QueriesInTheHttpLayer
{
    public function caught(Household $household, Chore $chore, Builder $passedIn): void
    {
        Chore::query()
            ->where('name', 'Bins')
            ->get();

        $household->chores()->count();

        $query = $household
            ->chores();
        $query->count();

        $passedIn->get();

        $chore->update(['name' => 'Bins']);

        $chore->refresh();

        Chore::find(1);

        DB::table('chores')->count();
    }

    public function allowed(Chore $chore, User $user): ?string
    {
        $user->membershipIn($chore->household_id);
        $user->forgetMemberships();

        return $chore->assignee?->name ?? $chore->name;
    }
}
