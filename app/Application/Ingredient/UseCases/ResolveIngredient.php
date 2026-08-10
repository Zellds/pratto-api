<?php

namespace App\Application\Ingredient\UseCases;

use App\Application\Ingredient\DTOs\ResolveIngredientInput;
use App\Domain\Ingredient\Ingredient;
use App\Domain\Ingredient\IngredientName;
use App\Domain\Ingredient\IngredientRepositoryInterface;
use App\Domain\Shared\Ulid;
use InvalidArgumentException;
use RuntimeException;

final readonly class ResolveIngredient
{
    public function __construct(private IngredientRepositoryInterface $ingredients) {}

    public function __invoke(ResolveIngredientInput $input): Ingredient
    {
        if ($input->ingredientId !== null) {
            $ingredient = $this->ingredients->findById(Ulid::fromString($input->ingredientId));

            if ($ingredient === null) {
                throw new RuntimeException("Ingredient \"{$input->ingredientId}\" not found.");
            }

            return $ingredient;
        }

        if ($input->ingredientName === null) {
            throw new InvalidArgumentException('Either ingredientId or ingredientName must be given.');
        }

        $name = IngredientName::fromString($input->ingredientName);
        $existing = $this->ingredients->findByNormalizedName($name->normalized());

        if ($existing !== null) {
            return $existing;
        }

        $ingredient = Ingredient::createProvisional(Ulid::generate(), $name);
        $this->ingredients->save($ingredient);

        return $ingredient;
    }
}
