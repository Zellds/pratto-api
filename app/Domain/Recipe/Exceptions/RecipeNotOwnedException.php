<?php

namespace App\Domain\Recipe\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class RecipeNotOwnedException extends RuntimeException
{
    public static function forRecipe(Ulid $id): self
    {
        return new self(sprintf('Recipe "%s" is not owned by the current user.', $id->value()));
    }
}
