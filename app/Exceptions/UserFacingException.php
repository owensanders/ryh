<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A failure whose message is written for the person using the app. These are
 * the only exceptions whose messages ever reach the screen: anything else
 * (a database error, a provider SDK error) is a 500 with no detail. Extends
 * Exception rather than RuntimeException on purpose, so a catch of one can
 * never swallow the other.
 *
 * Expected outcomes, not bugs, so they aren't reported. A subclass wrapping a
 * real failure reports the cause itself before throwing.
 */
abstract class UserFacingException extends Exception implements ShouldntReport
{
    /** The form error key the page shows this message under. */
    abstract public function errorKey(): string;

    /** Laravel calls this in place of its default rendering. */
    public function render(Request $request): Response
    {
        if ($request->expectsJson() && ! $request->hasHeader('X-Inertia')) {
            return response()->json([
                'message' => $this->getMessage(),
                'errors' => [$this->errorKey() => [$this->getMessage()]],
            ], 422);
        }

        return back()->withErrors([$this->errorKey() => $this->getMessage()]);
    }
}
