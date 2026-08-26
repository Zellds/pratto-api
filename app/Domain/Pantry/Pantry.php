<?php

namespace App\Domain\Pantry;

use App\Domain\Pantry\Exceptions\PantryNotOwnedException;
use App\Domain\Shared\Ulid;
use InvalidArgumentException;

/**
 * Aggregate root for a pantry: owner and name. Membership and items are
 * modeled as separate entities (PantryMembership, PantryItem) because they
 * change independently and far more often than the pantry's own identity.
 */
final class Pantry
{
    private function __construct(
        private readonly Ulid $id,
        private readonly Ulid $ownerId,
        private string $name,
    ) {}

    public static function create(Ulid $id, Ulid $ownerId, string $name): self
    {
        $pantry = new self($id, $ownerId, '');
        $pantry->applyName($name);

        return $pantry;
    }

    public static function reconstitute(Ulid $id, Ulid $ownerId, string $name): self
    {
        return new self($id, $ownerId, $name);
    }

    public function assertOwnedBy(Ulid $userId): void
    {
        if (! $this->ownerId->equals($userId)) {
            throw PantryNotOwnedException::forPantry($this->id);
        }
    }

    private function applyName(string $name): void
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Pantry name cannot be empty.');
        }

        $this->name = $trimmed;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function ownerId(): Ulid
    {
        return $this->ownerId;
    }

    public function name(): string
    {
        return $this->name;
    }
}
