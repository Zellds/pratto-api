<?php

namespace App\Domain\Media;

use InvalidArgumentException;

final readonly class FocalPoint
{
    private function __construct(
        private float $x,
        private float $y,
    ) {}

    public static function create(float $x, float $y): self
    {
        if ($x < 0.0 || $x > 1.0) {
            throw new InvalidArgumentException('Focal point x must be between 0.0 and 1.0.');
        }

        if ($y < 0.0 || $y > 1.0) {
            throw new InvalidArgumentException('Focal point y must be between 0.0 and 1.0.');
        }

        return new self($x, $y);
    }

    public static function center(): self
    {
        return new self(0.5, 0.5);
    }

    public function x(): float
    {
        return $this->x;
    }

    public function y(): float
    {
        return $this->y;
    }
}
