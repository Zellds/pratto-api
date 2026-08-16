<?php

namespace App\Domain\User\Exceptions;

use App\Domain\Shared\Ulid;
use RuntimeException;

final class AvatarMediaNotOwnedException extends RuntimeException
{
    public static function forMedia(Ulid $id): self
    {
        return new self(sprintf('Media "%s" cannot be used as an avatar by this user.', $id->value()));
    }
}
