<?php

namespace App\Domain\Media\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class MediaNotOwnedException extends RuntimeException
{
    public static function forMedia(Ulid $id): self
    {
        return new self(sprintf('Media "%s" is not owned by the current user.', $id->value()));
    }
}
