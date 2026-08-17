<?php

namespace App\Domain\Ingredient;

use App\Domain\Ingredient\Enums\IngredientStatus;
use App\Domain\Shared\Ulid;

final readonly class Ingredient
{
    private function __construct(
        private Ulid $id,
        private IngredientName $name,
        private IngredientStatus $status,
    ) {}

    public static function createProvisional(Ulid $id, IngredientName $name): self
    {
        return new self($id, $name, IngredientStatus::Provisional);
    }

    public static function reconstitute(Ulid $id, IngredientName $name, IngredientStatus $status): self
    {
        return new self($id, $name, $status);
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function name(): IngredientName
    {
        return $this->name;
    }

    public function status(): IngredientStatus
    {
        return $this->status;
    }
}
