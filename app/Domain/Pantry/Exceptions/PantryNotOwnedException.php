<?php

namespace App\Domain\Pantry\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class PantryNotOwnedException extends RuntimeException
{
    public static function forPantry(Ulid $id): self
    {
        return new self(sprintf('Pantry "%s" is not owned by the current user.', $id->value()));
    }
}
