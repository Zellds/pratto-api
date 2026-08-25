<?php

namespace App\Domain\Rating;

use InvalidArgumentException;

final readonly class Score
{
    private function __construct(private float $value) {}

    public static function create(float $value): self
    {
        if ($value < 1.0 || $value > 5.0) {
            throw new InvalidArgumentException('Score must be between 1.0 and 5.0.');
        }

        if (fmod($value * 2, 1.0) !== 0.0) {
            throw new InvalidArgumentException('Score must be in increments of 0.5.');
        }

        return new self($value);
    }

    public function value(): float
    {
        return $this->value;
    }
}
