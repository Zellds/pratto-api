<?php

namespace App\Domain\User\Exceptions;

use RuntimeException;

final class UserBannedException extends RuntimeException
{
    public static function forReason(?string $reason): self
    {
        return new self($reason !== null && $reason !== '' ? sprintf('Account banned: %s', $reason) : 'Account banned.');
    }
}
