<?php

declare(strict_types=1);

namespace App\Exceptions;

/** An upload that would take the household past its document storage. */
final class DocumentQuotaExceededException extends UserFacingException
{
    public function __construct(string $name)
    {
        parent::__construct("There isn't room for “{$name}”. Your household's document storage is full; delete something to make space.");
    }

    public function errorKey(): string
    {
        return 'file';
    }
}
