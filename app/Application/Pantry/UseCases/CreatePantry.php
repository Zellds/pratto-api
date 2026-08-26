<?php

// app/Application/Pantry/UseCases/CreatePantry.php

namespace App\Application\Pantry\UseCases;

use App\Application\Pantry\DTOs\PantryOutput;
use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryLimitExceededException;
use App\Domain\Pantry\Pantry;
use App\Domain\Pantry\PantryLimits;
use App\Domain\Shared\Ulid;

final readonly class CreatePantry
{
    public function __construct(private PantryRepositoryInterface $pantries) {}

    public function __invoke(string $ownerId, string $name): PantryOutput
    {
        $ownerUlid = Ulid::fromString($ownerId);

        if ($this->pantries->countPantriesForUser($ownerUlid) >= PantryLimits::MAX_PANTRIES_PER_USER) {
            throw PantryLimitExceededException::forUser($ownerUlid);
        }

        $pantry = Pantry::create(Ulid::generate(), $ownerUlid, $name);
        $this->pantries->save($pantry);

        return PantryOutput::fromDomain($pantry, 'owner');
    }
}
