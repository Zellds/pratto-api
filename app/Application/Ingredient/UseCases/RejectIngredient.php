<?php

namespace App\Application\Ingredient\UseCases;

use App\Application\Ingredient\DTOs\IngredientOutput;
use App\Domain\Ingredient\Contracts\IngredientRepositoryInterface;
use App\Domain\Ingredient\Exceptions\IngredientNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class RejectIngredient
{
    public function __construct(private IngredientRepositoryInterface $ingredients) {}

    public function __invoke(string $ingredientId): IngredientOutput
    {
        $id = Ulid::fromString($ingredientId);
        $ingredient = $this->ingredients->findById($id);

        if ($ingredient === null) {
            throw IngredientNotFoundException::forId($id);
        }

        $rejected = $ingredient->reject();
        $this->ingredients->save($rejected);

        return new IngredientOutput($rejected->id()->value(), $rejected->name()->value(), $rejected->status()->value);
    }
}
