<?php

declare(strict_types=1);

namespace Tests\PHPStan\Fixtures\Http;

/** Controllers may abort: UserFacingErrorsRule reports nothing here. */
class AbortInAController
{
    public function show(): void
    {
        abort(404);
    }
}
