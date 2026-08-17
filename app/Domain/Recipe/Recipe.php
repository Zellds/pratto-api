<?php

namespace App\Domain\Recipe;

use App\Domain\Shared\Ulid;
use InvalidArgumentException;

/**
 * Aggregate root for a recipe: owner, content (title/description/portions/prep time),
 * ingredients, steps and its publication status.
 *
 * @SuppressWarnings("PHPMD.TooManyPublicMethods")
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class Recipe
{
    /**
     * @param  list<RecipeIngredient>  $ingredients
     * @param  list<RecipeStep>  $steps
     */
    private function __construct(
        private readonly Ulid $id,
        private readonly Ulid $ownerId,
        private string $title,
        private string $description,
        private int $portions,
        private int $prepTimeMinutes,
        private RecipeStatus $status,
        private array $ingredients,
        private array $steps,
        private ?Ulid $coverMediaId = null,
    ) {}

    /**
     * @param  list<RecipeIngredient>  $ingredients
     * @param  list<RecipeStep>  $steps
     */
    public static function create(
        Ulid $id,
        Ulid $ownerId,
        string $title,
        string $description,
        int $portions,
        int $prepTimeMinutes,
        array $ingredients,
        array $steps,
        ?Ulid $coverMediaId = null,
    ): self {
        $recipe = new self($id, $ownerId, '', '', 1, 1, RecipeStatus::Draft, [], [], $coverMediaId);
        $recipe->applyContent($title, $description, $portions, $prepTimeMinutes, $ingredients, $steps);

        return $recipe;
    }

    /**
     * @param  list<RecipeIngredient>  $ingredients
     * @param  list<RecipeStep>  $steps
     */
    public static function reconstitute(
        Ulid $id,
        Ulid $ownerId,
        string $title,
        string $description,
        int $portions,
        int $prepTimeMinutes,
        RecipeStatus $status,
        array $ingredients,
        array $steps,
        ?Ulid $coverMediaId = null,
    ): self {
        return new self($id, $ownerId, $title, $description, $portions, $prepTimeMinutes, $status, $ingredients, $steps, $coverMediaId);
    }

    /**
     * @param  list<RecipeIngredient>  $ingredients
     * @param  list<RecipeStep>  $steps
     */
    public function update(
        string $title,
        string $description,
        int $portions,
        int $prepTimeMinutes,
        array $ingredients,
        array $steps,
        ?Ulid $coverMediaId = null,
    ): void {
        $this->applyContent($title, $description, $portions, $prepTimeMinutes, $ingredients, $steps);
        $this->coverMediaId = $coverMediaId;
        $this->status = RecipeStatus::Draft;
    }

    public function publish(): void
    {
        if ($this->status !== RecipeStatus::Draft) {
            throw new InvalidRecipeStatusTransitionException($this->status, RecipeStatus::PendingReview);
        }

        $this->status = RecipeStatus::PendingReview;
    }

    public function assertOwnedBy(Ulid $userId): void
    {
        if (! $this->ownerId->equals($userId)) {
            throw RecipeNotOwnedException::forRecipe($this->id);
        }
    }

    public function isVisibleTo(?Ulid $viewerId): bool
    {
        if (in_array($this->status, [RecipeStatus::PendingReview, RecipeStatus::Published], true)) {
            return true;
        }

        return $viewerId !== null && $this->ownerId->equals($viewerId);
    }

    /**
     * @return list<RecipeIngredient>
     */
    public function scaledIngredients(?int $requestedPortions): array
    {
        if ($requestedPortions === null || $requestedPortions === $this->portions) {
            return $this->ingredients;
        }

        if ($requestedPortions <= 0) {
            throw new InvalidArgumentException('Requested portions must be greater than zero.');
        }

        $ratio = $requestedPortions / $this->portions;

        return array_map(static fn (RecipeIngredient $ingredient) => $ingredient->scaledBy($ratio), $this->ingredients);
    }

    /**
     * @param  list<RecipeIngredient>  $ingredients
     * @param  list<RecipeStep>  $steps
     */
    private function applyContent(
        string $title,
        string $description,
        int $portions,
        int $prepTimeMinutes,
        array $ingredients,
        array $steps,
    ): void {
        $trimmedTitle = trim($title);

        if ($trimmedTitle === '') {
            throw new InvalidArgumentException('Recipe title cannot be empty.');
        }

        if ($portions <= 0) {
            throw new InvalidArgumentException('Portions must be greater than zero.');
        }

        if ($prepTimeMinutes <= 0) {
            throw new InvalidArgumentException('Prep time must be greater than zero.');
        }

        if ($ingredients === []) {
            throw new InvalidArgumentException('A recipe must have at least one ingredient.');
        }

        if ($steps === []) {
            throw new InvalidArgumentException('A recipe must have at least one step.');
        }

        $this->title = $trimmedTitle;
        $this->description = trim($description);
        $this->portions = $portions;
        $this->prepTimeMinutes = $prepTimeMinutes;
        $this->ingredients = $ingredients;
        $this->steps = $steps;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function ownerId(): Ulid
    {
        return $this->ownerId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function portions(): int
    {
        return $this->portions;
    }

    public function prepTimeMinutes(): int
    {
        return $this->prepTimeMinutes;
    }

    public function status(): RecipeStatus
    {
        return $this->status;
    }

    /**
     * @return list<RecipeIngredient>
     */
    public function ingredients(): array
    {
        return $this->ingredients;
    }

    /**
     * @return list<RecipeStep>
     */
    public function steps(): array
    {
        return $this->steps;
    }

    public function coverMediaId(): ?Ulid
    {
        return $this->coverMediaId;
    }
}
