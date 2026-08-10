<?php

namespace App\Domain\Recipe;

use InvalidArgumentException;

final readonly class RecipeStep
{
    private function __construct(
        private int $position,
        private string $instruction,
    ) {}

    public static function create(int $position, string $instruction): self
    {
        $trimmed = trim($instruction);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Step instruction cannot be empty.');
        }

        if ($position < 0) {
            throw new InvalidArgumentException('Step position cannot be negative.');
        }

        return new self($position, $trimmed);
    }

    public function position(): int
    {
        return $this->position;
    }

    public function instruction(): string
    {
        return $this->instruction;
    }
}
