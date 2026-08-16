<?php

namespace App\Domain\Recipe;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class CoverMediaNotOwnedException extends RuntimeException
{
    public static function forMedia(Ulid $id): self
    {
        return new self(sprintf('Media "%s" cannot be used as a cover by this user.', $id->value()));
    }
}
