<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** The chore board, with who can be reminded and when. */
class ChoreBoardScreenData extends Data
{
    /**
     * @param  array<int, ChoreColumnData>  $columns
     * @param  array<int, MemberScoreData>  $scores
     * @param  array<int, MemberData>  $members
     */
    public function __construct(
        public array $columns,
        public array $scores,
        public array $members,
        /** When "Notify Chores" next works, as JSON time — null once it does. */
        public ?string $notifyChoresAvailableAt,
    ) {}
}
