<?php

namespace App\Http\Resources;

use App\Application\Recipe\DTOs\RecipeOutput;
use App\Domain\Ingredient\Contracts\IngredientRepositoryInterface;
use App\Domain\Media\Contracts\MediaRepositoryInterface;
use App\Domain\Media\Contracts\MediaUrlSignerInterface;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property RecipeOutput $resource */
class RecipeResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        $owner = $this->resolveOwner();
        $cover = $this->resolveCoverUrls();
        $ingredientNames = $this->resolveIngredientNames();

        return [
            'id' => $this->resource->id,
            'ownerId' => $this->resource->ownerId,
            'ownerUsername' => $owner?->username()->value(),
            'ownerDisplayName' => $owner?->displayName()->value(),
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'portions' => $this->resource->portions,
            'prepTimeMinutes' => $this->resource->prepTimeMinutes,
            'status' => $this->resource->status,
            'coverMediaId' => $this->resource->coverMediaId,
            'coverThumbnailUrl' => $cover['thumbnail'] ?? null,
            'coverDisplayUrl' => $cover['display'] ?? null,
            'rejectionReason' => $this->resource->rejectionReason,
            'averageRating' => $this->resource->averageRating !== null
                ? round($this->resource->averageRating, 1)
                : null,
            'ratingsCount' => $this->resource->ratingsCount,
            'ingredients' => array_map(fn ($ingredient) => [
                'ingredientId' => $ingredient->ingredientId,
                'ingredientName' => $ingredientNames[$ingredient->ingredientId] ?? null,
                'quantity' => $ingredient->quantity,
                'unit' => $ingredient->unit,
                'position' => $ingredient->position,
                'isOptional' => $ingredient->isOptional,
            ], $this->resource->ingredients),
            'steps' => array_map(static fn ($step) => [
                'position' => $step->position,
                'instruction' => $step->instruction,
            ], $this->resource->steps),
        ];
    }

    private function resolveOwner(): ?User
    {
        return app(UserRepositoryInterface::class)->findById(Ulid::fromString($this->resource->ownerId));
    }

    /**
     * One query for the whole recipe (not one per line), mirroring how the owner and
     * cover are resolved above.
     *
     * @return array<string, string> ingredient id => display name
     */
    private function resolveIngredientNames(): array
    {
        $ids = array_map(
            static fn ($ingredient) => Ulid::fromString($ingredient->ingredientId),
            $this->resource->ingredients,
        );

        $names = [];
        foreach (app(IngredientRepositoryInterface::class)->findByIds($ids) as $id => $ingredient) {
            $names[$id] = $ingredient->name()->value();
        }

        return $names;
    }

    /**
     * @return array{thumbnail: string, display: string}|null
     */
    private function resolveCoverUrls(): ?array
    {
        if ($this->resource->coverMediaId === null) {
            return null;
        }

        $media = app(MediaRepositoryInterface::class)->findById(Ulid::fromString($this->resource->coverMediaId));

        if ($media === null || $media->status() === MediaStatus::Rejected) {
            return null;
        }

        return app(MediaUrlSignerInterface::class)->signedUrlsFor($media->storageKey());
    }
}
