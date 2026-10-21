<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * For a form request's prepareForValidation(): tidy what was sent so that
 * validated() is already what gets stored. Anything the wrong shape is left
 * as it is, for the rules to reject.
 */
trait NormalisesInputTrait
{
    /** Text without surrounding spaces. */
    protected function trimmed(string $key): mixed
    {
        $value = $this->input($key);

        return is_string($value) ? trim($value) : $value;
    }

    /** Text without surrounding spaces, and nothing at all as null. */
    protected function trimmedOrNull(string $key): mixed
    {
        $value = $this->trimmed($key);

        return $value === '' ? null : $value;
    }

    /**
     * An id as an int, so it compares with `===`; nothing at all as null. A
     * plain form sends every value as text.
     */
    protected function idOrNull(string $key): mixed
    {
        $value = $this->input($key);

        return match (true) {
            $value === null, $value === '' => null,
            is_numeric($value) => (int) $value,
            default => $value,
        };
    }
}
