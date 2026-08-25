<?php

// app/Domain/Follow/Exceptions/CannotFollowSelfException.php

namespace App\Domain\Follow\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class CannotFollowSelfException extends RuntimeException
{
    public static function forUser(Ulid $id): self
    {
        return new self(sprintf('User "%s" cannot follow themselves.', $id->value()));
    }
}
