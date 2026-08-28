<?php

namespace App\Domain\Moderation\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class ReportNotFoundException extends RuntimeException
{
    public static function forId(Ulid $id): self
    {
        return new self(sprintf('Report "%s" not found.', $id->value()));
    }
}
