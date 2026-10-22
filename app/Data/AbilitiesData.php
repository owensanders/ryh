<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

/**
 * What the person viewing a page may do to one record, answered by its
 * policy, so pages never work it out from the role. The viewer is passed in,
 * not read from the session. Only the abilities asked for are sent: a
 * calendar event has no `toggle`.
 */
class AbilitiesData extends Data
{
    public function __construct(
        public bool|Optional $update = new Optional(),
        public bool|Optional $delete = new Optional(),
        public bool|Optional $toggle = new Optional(),
    ) {}

    public static function for(User $viewer, Model $model, string ...$abilities): self
    {
        $gate = Gate::forUser($viewer);
        $can = [];

        foreach ($abilities as $ability) {
            $can[$ability] = $gate->allows($ability, $model);
        }

        return new self(...$can);
    }
}
