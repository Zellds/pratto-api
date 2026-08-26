<?php

// app/Application/Pantry/UseCases/ListMyPantries.php

namespace App\Application\Pantry\UseCases;

use App\Application\Pantry\DTOs\PantryOutput;
use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Pantry;
use App\Domain\Shared\Ulid;

final readonly class ListMyPantries
{
    public function __construct(private PantryRepositoryInterface $pantries) {}

    /**
     * @return list<PantryOutput>
     */
    public function __invoke(string $userId): array
    {
        $userUlid = Ulid::fromString($userId);

        return array_map(
            fn (Pantry $pantry) => PantryOutput::fromDomain(
                $pantry,
                $pantry->ownerId()->equals($userUlid) ? 'owner' : 'member',
            ),
            $this->pantries->pantriesForUser($userUlid),
        );
    }
}
