<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Household;
use Illuminate\Http\Request;
use LogicException;

/**
 * The household a request is for. ResolveHouseholdMiddleware sets it from the
 * /h/{slug} URL, after checking they belong there.
 *
 * Kept on the request rather than in the container, so it can't outlive the
 * request it was resolved for (a queued job, or the next request in a test,
 * starts with none). Every read names the request: controllers, form requests
 * and middleware have one. Policies and Data never read it; they're given the
 * household or the record.
 */
final class CurrentHouseholdHelper
{
    private const string HOUSEHOLD = 'ryh.household';

    public static function set(Request $request, Household $household): void
    {
        $request->attributes->set(self::HOUSEHOLD, $household);
    }

    /** @throws LogicException Outside a household route, where there's no household to give. */
    public static function household(Request $request): Household
    {
        return self::find($request) ?? throw new LogicException('No household for this request: is the route inside the h/{household} group?');
    }

    public static function find(Request $request): ?Household
    {
        $household = $request->attributes->get(self::HOUSEHOLD);

        return $household instanceof Household ? $household : null;
    }

    public static function id(Request $request): ?int
    {
        return self::find($request)?->id;
    }
}
