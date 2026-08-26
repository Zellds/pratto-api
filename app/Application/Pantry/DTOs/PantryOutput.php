<?php

// app/Application/Pantry/DTOs/PantryOutput.php

namespace App\Application\Pantry\DTOs;

use App\Domain\Pantry\Pantry;

final readonly class PantryOutput
{
    public function __construct(
        public string $id,
        public string $ownerId,
        public string $name,
        public string $role,
    ) {}

    public static function fromDomain(Pantry $pantry, string $role): self
    {
        return new self($pantry->id()->value(), $pantry->ownerId()->value(), $pantry->name(), $role);
    }
}
