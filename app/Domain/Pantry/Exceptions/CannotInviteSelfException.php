<?php

namespace App\Domain\Pantry\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class CannotInviteSelfException extends RuntimeException
{
    public static function forUser(Ulid $userId): self
    {
        return new self(sprintf('User "%s" cannot invite themselves to their own pantry.', $userId->value()));
    }
}
