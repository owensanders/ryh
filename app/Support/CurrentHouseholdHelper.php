<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\HouseholdUser;
use Illuminate\Http\Request;
use LogicException;

/**
 * The household this request is for, and the signed-in person's membership
 * of it. ResolveHouseholdMiddleware sets it from the /h/{slug} URL, after
 * checking they belong there.
 *
 * Kept on the request rather than in the container, so it can't outlive the
 * request it was resolved for (a queued job, or the next request in a test,
 * starts with none). Outside a household route there is none: id() and
 * role() are null, and household() is a bug.
 */
final class CurrentHouseholdHelper
{
    private const string HOUSEHOLD = 'ryh.household';

    private const string MEMBERSHIP = 'ryh.membership';

    public static function set(Request $request, Household $household, HouseholdUser $membership): void
    {
        $request->attributes->set(self::HOUSEHOLD, $household);
        $request->attributes->set(self::MEMBERSHIP, $membership);
    }

    /** @throws LogicException Outside a household route, where there's no household to give. */
    public static function household(?Request $request = null): Household
    {
        return self::find($request) ?? throw new LogicException('No household for this request: is the route inside the h/{household} group?');
    }

    public static function find(?Request $request = null): ?Household
    {
        $household = ($request ?? request())->attributes->get(self::HOUSEHOLD);

        return $household instanceof Household ? $household : null;
    }

    public static function id(?Request $request = null): ?int
    {
        return self::find($request)?->id;
    }

    public static function role(?Request $request = null): ?HouseholdRoleEnum
    {
        $membership = ($request ?? request())->attributes->get(self::MEMBERSHIP);

        return $membership instanceof HouseholdUser ? $membership->role : null;
    }
}
