<?php

namespace App\Domain\Pantry\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class PantryNotFoundException extends RuntimeException
{
    public static function forId(Ulid $id): self
    {
        return new self(sprintf('Pantry "%s" not found.', $id->value()));
    }
}
