<?php

declare(strict_types=1);

namespace Tests\Unit\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;
use Tests\PHPStan\TypedActionInputRule;

/** @extends RuleTestCase<TypedActionInputRule> */
class TypedActionInputRuleTest extends RuleTestCase
{
    private const string ADVICE = " Give it a typed input from app/Inputs, built by the form request's toInput().";

    protected function getRule(): Rule
    {
        return new TypedActionInputRule(['Tests\\PHPStan\\Fixtures\\Actions\\'], []);
    }

    #[Test]
    public function catchesKeyedArraysButNotInputsOrLists(): void
    {
        $this->analyse([__DIR__.'/../../PHPStan/Fixtures/Actions/ArraysInAnAction.php'], [
            ['handle() takes $data as a keyed array (array<string, mixed>).'.self::ADVICE, 19],
            ['handle() takes $labels as a keyed array (array<string, string>).'.self::ADVICE, 19],
            ['handle() takes $ids as a keyed array (array<int, int>).'.self::ADVICE, 19],
            ['handle() takes $untyped as a keyed array (array).'.self::ADVICE, 19],
        ]);
    }
}
