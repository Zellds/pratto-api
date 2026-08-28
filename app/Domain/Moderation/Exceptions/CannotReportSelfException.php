<?php

namespace App\Domain\Moderation\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class CannotReportSelfException extends RuntimeException
{
    public static function forUser(Ulid $userId): self
    {
        return new self(sprintf('User "%s" cannot report themselves.', $userId->value()));
    }
}
