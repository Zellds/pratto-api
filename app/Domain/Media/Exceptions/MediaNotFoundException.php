<?php

namespace App\Domain\Media\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class MediaNotFoundException extends RuntimeException
{
    public static function forId(Ulid $id): self
    {
        return new self(sprintf('Media "%s" not found.', $id->value()));
    }
}
