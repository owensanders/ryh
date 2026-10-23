<?php

declare(strict_types=1);

namespace Tests\PHPStan\Fixtures\Actions;

use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;

/** Breaks UserFacingErrorsRule on purpose; the line numbers are asserted in UserFacingErrorsRuleTest. */
class ErrorsInAnAction
{
    public function aborts(): void
    {
        abort(404);
    }

    public function abortsIf(bool $missing): void
    {
        abort_if($missing, 404);
    }

    public function catches(): void
    {
        try {
            $this->work();
        } catch (RuntimeException $e) {
        }

        try {
            $this->work();
        } catch (LogicException|RuntimeException $e) {
        }

        try {
            $this->work();
        } catch (LogicException $e) {
        }
    }

    public function validates(): void
    {
        throw ValidationException::withMessages(['email' => 'Already a member.']);
    }

    private function work(): void {}
}
