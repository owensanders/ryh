<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * The chore board, with who can be reminded and when. The columns and scores
 * share one read of the board and every chore write reloads both; the rest
 * are closures, so a partial reload that doesn't ask for them skips them.
 */
class ChoreBoardScreenData extends Data
{
    /**
     * @param  array<int, ChoreColumnData>  $columns
     * @param  array<int, MemberScoreData>  $scores
     * @param  Lazy|array<int, MemberData>  $members
     * @param  Lazy|string|null  $notifyChoresAvailableAt  When "Notify Chores" next works, as JSON time — null once it does.
     */
    public function __construct(
        public array $columns,
        public array $scores,
        public Lazy|array $members,
        public Lazy|string|null $notifyChoresAvailableAt,
    ) {}
}
