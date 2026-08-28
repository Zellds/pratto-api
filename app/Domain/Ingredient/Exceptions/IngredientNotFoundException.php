<?php

namespace App\Domain\Ingredient\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class IngredientNotFoundException extends RuntimeException
{
    public static function forId(Ulid $id): self
    {
        return new self(sprintf('Ingredient "%s" not found.', $id->value()));
    }
}
