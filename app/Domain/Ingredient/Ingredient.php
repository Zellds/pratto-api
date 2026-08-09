<?php

namespace App\Domain\Ingredient;

use App\Domain\Shared\Ulid;

final class Ingredient
{
    private function __construct(
        private readonly Ulid $id,
        private readonly IngredientName $name,
        private readonly IngredientStatus $status,
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
