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

    /**
     * Text as one plain line: control and invisible formatting characters
     * removed, runs of whitespace (newlines included) as single spaces, and
     * nothing at all as null. For text that's passed on, like an AI prompt's.
     */
    protected function plainText(string $key): mixed
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return $value;
        }

        $value = preg_replace('/[\p{Cc}\p{Cf}]+/u', ' ', $value) ?? '';
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value === '' ? null : $value;
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
