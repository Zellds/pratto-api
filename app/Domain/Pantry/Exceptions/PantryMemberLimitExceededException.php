<?php

namespace App\Domain\Pantry\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class PantryMemberLimitExceededException extends RuntimeException
{
    public static function forPantry(Ulid $pantryId): self
    {
        return new self(sprintf('Pantry "%s" already has the maximum number of members.', $pantryId->value()));
    }
}
