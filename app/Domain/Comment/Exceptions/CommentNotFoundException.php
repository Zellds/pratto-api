<?php

namespace App\Domain\Comment\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class CommentNotFoundException extends RuntimeException
{
    public static function forId(Ulid $id): self
    {
        return new self(sprintf('Comment "%s" not found.', $id->value()));
    }
}
