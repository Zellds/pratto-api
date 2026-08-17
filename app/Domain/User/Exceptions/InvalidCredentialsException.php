<?php

namespace App\Domain\User\Exceptions;

use RuntimeException;

final class InvalidCredentialsException extends RuntimeException
{
    public static function create(): self
    {
        return new self('Invalid credentials.');
    }
}
