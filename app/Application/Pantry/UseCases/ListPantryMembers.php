<?php

// app/Application/Pantry/UseCases/ListPantryMembers.php

namespace App\Application\Pantry\UseCases;

use App\Application\Pantry\DTOs\PantryMemberOutput;
use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\User;

final readonly class ListPantryMembers
{
    public function __construct(
        private PantryRepositoryInterface $pantries,
        private PantryMembershipRepositoryInterface $memberships,
        private UserRepositoryInterface $users,
    ) {}

    /**
     * @return list<PantryMemberOutput>
     */
    public function __invoke(string $pantryId, string $actorId): array
    {
        $pantryUlid = Ulid::fromString($pantryId);
        $actorUlid = Ulid::fromString($actorId);

        $pantry = $this->pantries->findById($pantryUlid);

        if ($pantry === null || ! $this->memberships->hasAccess($pantryUlid, $actorUlid)) {
            throw PantryNotFoundException::forId($pantryUlid);
        }

        $owner = $this->users->findById($pantry->ownerId());
        $output = [$this->toOutput($owner, 'owner')];

        foreach ($this->memberships->memberIdsFor($pantryUlid) as $memberId) {
            $output[] = $this->toOutput($this->users->findById($memberId), 'member');
        }

        return $output;
    }

    private function toOutput(User $user, string $role): PantryMemberOutput
    {
        return new PantryMemberOutput($user->id()->value(), $user->username()->value(), $user->displayName()->value(), $role);
    }
}
