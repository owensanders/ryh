<?php

declare(strict_types=1);

namespace Tests\PHPStan\Fixtures\Http;

use App\Http\Requests\MoveChoreRequest;
use Illuminate\Http\Request;

/** TypedRequestValuesRule: raw reads are caught; typed getters, the user and the session aren't. */
class RawReadsInAController
{
    public function handle(Request $request, MoveChoreRequest $move): void
    {
        $request->validated('status');
        $request->query('month');
        $move->boolean('enabled');
        $held = $move;
        $held->input('name');

        $move->status();
        $request->user();
        $request->session();
    }
}
