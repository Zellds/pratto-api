<?php

namespace App\Application\Ingredient\UseCases;

use App\Application\Ingredient\DTOs\IngredientOutput;
use App\Domain\Ingredient\IngredientRepositoryInterface;

final readonly class SearchIngredients
{
    public function __construct(private IngredientRepositoryInterface $ingredients) {}

    /**
     * @return list<IngredientOutput>
     */
    public function __invoke(string $term): array
    {
        return array_map(
            static fn ($ingredient) => new IngredientOutput(
                $ingredient->id()->value(),
                $ingredient->name()->value(),
                $ingredient->status()->value,
            ),
            $this->ingredients->search($term),
        );
    }
}
