<?php

declare(strict_types=1);

namespace Tests\Unit\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;
use Tests\PHPStan\TypedRequestValuesRule;

/** @extends RuleTestCase<TypedRequestValuesRule> */
class TypedRequestValuesRuleTest extends RuleTestCase
{
    private const string ADVICE = ' Give the form request a typed getter, or toInput() for a form.';

    protected function getRule(): Rule
    {
        return new TypedRequestValuesRule(['Tests\\PHPStan\\Fixtures\\Http\\'], []);
    }

    #[Test]
    public function catchesRawReadsHoweverTheRequestIsHeld(): void
    {
        $this->analyse([__DIR__.'/../../PHPStan/Fixtures/Http/RawReadsInAController.php'], [
            ['Reads the request in a controller: ->validated().'.self::ADVICE, 15],
            ['Reads the request in a controller: ->query().'.self::ADVICE, 16],
            ['Reads the request in a controller: ->boolean().'.self::ADVICE, 17],
            ['Reads the request in a controller: ->input().'.self::ADVICE, 19],
        ]);
    }
}
