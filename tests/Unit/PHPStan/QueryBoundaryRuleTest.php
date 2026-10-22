<?php

declare(strict_types=1);

namespace Tests\Unit\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;
use Tests\PHPStan\QueryBoundaryRule;

/** @extends RuleTestCase<QueryBoundaryRule> */
class QueryBoundaryRuleTest extends RuleTestCase
{
    private const string ADVICE = ' Move it into an action or view model, or ask the model (a method on it, a #[Scope] for reuse).';

    protected function getRule(): Rule
    {
        return new QueryBoundaryRule(['Tests\\PHPStan\\Fixtures\\Http\\']);
    }

    #[Test]
    public function catchesQueriesHoweverTheyreWritten(): void
    {
        $this->analyse([__DIR__.'/../../PHPStan/Fixtures/Http/QueriesInTheHttpLayer.php'], [
            ['Queries the Chore model: Chore::query().'.self::ADVICE, 18],
            ['Starts a query: ->chores().'.self::ADVICE, 22],
            // Split over lines and held in a variable.
            ['Starts a query: ->chores().'.self::ADVICE, 24],
            ['Runs a query: ->count().'.self::ADVICE, 26],
            ['Runs a query: ->get().'.self::ADVICE, 28],
            ['Writes to or reloads a model: ->update().'.self::ADVICE, 30],
            ['Writes to or reloads a model: ->refresh().'.self::ADVICE, 32],
            ['Queries the Chore model: Chore::find().'.self::ADVICE, 34],
            ['Queries the database: DB::table().'.self::ADVICE, 36],
        ]);
    }

    #[Test]
    public function letsActionsQuery(): void
    {
        $this->analyse([__DIR__.'/../../PHPStan/Fixtures/Actions/QueriesInAnAction.php'], []);
    }
}
