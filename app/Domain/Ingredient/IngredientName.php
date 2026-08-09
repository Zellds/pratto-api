<?php

namespace App\Domain\Ingredient;

use InvalidArgumentException;

final readonly class IngredientName
{
    private function __construct(private string $value, private string $normalizedValue) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Ingredient name cannot be empty.');
        }

        if (mb_strlen($trimmed) > 80) {
            throw new InvalidArgumentException('Ingredient name cannot exceed 80 characters.');
        }

        return new self($trimmed, self::normalize($trimmed));
    }

    public static function normalize(string $value): string
    {
        $lower = mb_strtolower(trim($value));
        $withoutAccents = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lower);
        $collapsed = preg_replace('/\s+/', ' ', $withoutAccents !== false ? $withoutAccents : $lower);

        return trim($collapsed ?? $lower);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function normalized(): string
    {
        return $this->normalizedValue;
    }
}
