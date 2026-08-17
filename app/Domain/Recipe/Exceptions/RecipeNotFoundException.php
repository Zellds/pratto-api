<?php

namespace App\Domain\Recipe\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class RecipeNotFoundException extends RuntimeException
{
    public static function forId(Ulid $id): self
    {
        return new self(sprintf('Recipe "%s" not found.', $id->value()));
    }
}
