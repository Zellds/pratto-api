<?php

// app/Application/Recipe/UseCases/SearchRecipes.php

namespace App\Application\Recipe\UseCases;

use App\Application\Recipe\DTOs\RecipeOutput;
use App\Domain\Recipe\Contracts\RecipeRepositoryInterface;
use App\Domain\Shared\Ulid;

final readonly class SearchRecipes
{
    public function __construct(private RecipeRepositoryInterface $recipes) {}

    /**
     * @return list<RecipeOutput>
     */
    public function __invoke(?string $term, ?string $mineOwnerId, int $page, int $perPage): array
    {
        $ownerId = $mineOwnerId === null ? null : Ulid::fromString($mineOwnerId);

        return array_map(
            static fn ($recipe) => RecipeOutput::fromDomain($recipe),
            $this->recipes->search($term, $ownerId, $page, $perPage),
        );
    }
}
