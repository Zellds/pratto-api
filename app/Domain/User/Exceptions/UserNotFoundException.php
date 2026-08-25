<?php

// app/Domain/User/Exceptions/UserNotFoundException.php

namespace App\Domain\User\Exceptions;

use App\Domain\User\Username;
use RuntimeException;

final class UserNotFoundException extends RuntimeException
{
    public static function forUsername(Username $username): self
    {
        return new self(sprintf('User "%s" not found.', $username->value()));
    }
}
