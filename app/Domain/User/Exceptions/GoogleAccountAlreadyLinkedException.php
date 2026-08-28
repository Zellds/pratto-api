<?php

namespace App\Domain\User\Exceptions;

use RuntimeException;

final class GoogleAccountAlreadyLinkedException extends RuntimeException
{
    public static function forGoogleId(string $googleId): self
    {
        return new self('This Google account is already linked to another user.');
    }
}
