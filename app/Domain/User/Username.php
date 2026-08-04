<?php

namespace App\Domain\User;

use InvalidArgumentException;

final readonly class Username
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        if (mb_strlen($value) < 3) {
            throw new InvalidArgumentException('Username must have at least 3 characters.');
        }

        if (! preg_match('/^[a-z0-9_]+$/', $value)) {
            throw new InvalidArgumentException('Username may only contain lowercase letters, numbers and underscore.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
