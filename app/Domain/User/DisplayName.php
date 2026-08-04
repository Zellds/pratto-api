<?php

namespace App\Domain\User;

use InvalidArgumentException;

final class DisplayName
{
    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Display name cannot be empty.');
        }

        if (mb_strlen($trimmed) > 80) {
            throw new InvalidArgumentException('Display name cannot exceed 80 characters.');
        }

        return new self($trimmed);
    }

    public function value(): string
    {
        return $this->value;
    }
}
