<?php

namespace App\Application\Ingredient\UseCases;

use App\Application\Ingredient\DTOs\IngredientOutput;
use App\Domain\Ingredient\Contracts\IngredientRepositoryInterface;
use App\Domain\Ingredient\Exceptions\IngredientNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class ApproveIngredient
{
    public function __construct(private IngredientRepositoryInterface $ingredients) {}

    public function __invoke(string $ingredientId): IngredientOutput
    {
        $id = Ulid::fromString($ingredientId);
        $ingredient = $this->ingredients->findById($id);

        if ($ingredient === null) {
            throw IngredientNotFoundException::forId($id);
        }

        $approved = $ingredient->approve();
        $this->ingredients->save($approved);

        return new IngredientOutput($approved->id()->value(), $approved->name()->value(), $approved->status()->value);
    }
}
