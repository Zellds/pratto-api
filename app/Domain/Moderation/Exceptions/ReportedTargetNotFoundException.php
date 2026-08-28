<?php

namespace App\Domain\Moderation\Exceptions;

use App\Domain\Moderation\Enums\ReportTargetType;
use App\Domain\Shared\Ulid;
use RuntimeException;

final class ReportedTargetNotFoundException extends RuntimeException
{
    public static function forTarget(ReportTargetType $targetType, Ulid $targetId): self
    {
        return new self(sprintf('%s "%s" not found.', ucfirst($targetType->value), $targetId->value()));
    }
}
