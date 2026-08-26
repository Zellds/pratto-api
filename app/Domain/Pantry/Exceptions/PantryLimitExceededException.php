<?php

namespace App\Domain\Pantry\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class PantryLimitExceededException extends RuntimeException
{
    public static function forUser(Ulid $userId): self
    {
        return new self(sprintf('User "%s" already has the maximum number of pantries.', $userId->value()));
    }
}
