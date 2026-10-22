<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

/**
 * What the signed-in person may do to one record, answered by its policy, so
 * pages never work it out from the role. Only the abilities asked for are
 * sent: a calendar event has no `toggle`.
 */
class AbilitiesData extends Data
{
    public function __construct(
        public bool|Optional $update = new Optional(),
        public bool|Optional $delete = new Optional(),
        public bool|Optional $toggle = new Optional(),
    ) {}

    public static function for(Model $model, string ...$abilities): self
    {
        $can = [];

        foreach ($abilities as $ability) {
            $can[$ability] = Gate::allows($ability, $model);
        }

        return new self(...$can);
    }
}
