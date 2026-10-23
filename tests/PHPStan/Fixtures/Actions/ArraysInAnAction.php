<?php

declare(strict_types=1);

namespace Tests\PHPStan\Fixtures\Actions;

use App\Inputs\ChoreInput;
use App\Models\Household;

/** TypedActionInputRule: a keyed array in handle() is caught; a typed input or a list isn't. */
class ArraysInAnAction
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $labels
     * @param  list<string>  $names
     * @param  array<int, int>  $ids
     */
    public function handle(Household $household, array $data, array $labels, ChoreInput $input, array $names, array $ids, array $untyped): void {}
}
