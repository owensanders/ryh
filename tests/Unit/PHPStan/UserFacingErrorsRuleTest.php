<?php

declare(strict_types=1);

namespace Tests\Unit\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;
use Tests\PHPStan\UserFacingErrorsRule;

/** @extends RuleTestCase<UserFacingErrorsRule> */
class UserFacingErrorsRuleTest extends RuleTestCase
{
    private const string ABORT = '() in an action or view model. Throw a UserFacingException or ModelNotFoundException instead.';

    private const string CATCH = 'Catches RuntimeException, which includes database errors. Catch the UserFacingException subclass you mean instead.';

    protected function getRule(): Rule
    {
        return new UserFacingErrorsRule(['Tests\\PHPStan\\Fixtures\\Actions\\'], 'Tests\\PHPStan\\Fixtures\\');
    }

    #[Test]
    public function catchesAbortsAndRuntimeExceptionCatchesInActions(): void
    {
        $this->analyse([__DIR__.'/../../PHPStan/Fixtures/Actions/ErrorsInAnAction.php'], [
            ['abort'.self::ABORT, 16],
            ['abort_if'.self::ABORT, 21],
            [self::CATCH, 28],
            // In a union, fully qualified.
            [self::CATCH, 33],
            ['ValidationException in an action or view model. Throw a UserFacingException subclass with a named constructor instead.', 44],
        ]);
    }

    #[Test]
    public function letsControllersAbort(): void
    {
        $this->analyse([__DIR__.'/../../PHPStan/Fixtures/Http/AbortInAController.php'], []);
    }
}
