<?php

namespace App\Domain\Shared;

use Symfony\Component\Uid\Ulid as SymfonyUlid;

final readonly class Ulid
{
    private function __construct(private string $value) {}

    public static function generate(): self
    {
        return new self((string) new SymfonyUlid);
    }

    public static function fromString(string $value): self
    {
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

    public function __toString(): string
    {
        return $this->value;
    }
}
