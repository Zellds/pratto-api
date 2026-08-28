<?php

namespace App\Domain\User\Exceptions;

use RuntimeException;

final class InvalidGoogleTokenException extends RuntimeException
{
    public static function create(): self
    {
        return new self('Invalid or expired Google token.');
    }
}
