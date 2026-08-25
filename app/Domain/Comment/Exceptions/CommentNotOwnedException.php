<?php

namespace App\Domain\Comment\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class CommentNotOwnedException extends RuntimeException
{
    public static function forComment(Ulid $id): self
    {
        return new self(sprintf('Comment "%s" is not owned by the current user.', $id->value()));
    }
}
