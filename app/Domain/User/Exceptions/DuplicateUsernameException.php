<?php

namespace App\Domain\User\Exceptions;

use App\Domain\User\Username;
use RuntimeException;

final class DuplicateUsernameException extends RuntimeException
{
    public static function forUsername(Username $username): self
    {
        return new self(sprintf('Username "%s" is already taken.', $username->value()));
    }
}
