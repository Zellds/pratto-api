<?php

use App\Domain\Pantry\PantryMembership;
use App\Domain\Shared\Ulid;

it('creates a membership for a user in a pantry', function () {
    $pantryId = Ulid::generate();
    $userId = Ulid::generate();

    $membership = PantryMembership::create(Ulid::generate(), $pantryId, $userId);

    expect($membership->pantryId()->equals($pantryId))->toBeTrue()
        ->and($membership->userId()->equals($userId))->toBeTrue()
        ->and($membership->createdAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('reconstitutes with the same field order as create', function () {
    $id = Ulid::generate();
    $pantryId = Ulid::generate();
    $userId = Ulid::generate();
    $createdAt = new DateTimeImmutable('2026-01-01 12:00:00');

    $membership = PantryMembership::reconstitute($id, $pantryId, $userId, $createdAt);

    expect($membership->id()->equals($id))->toBeTrue()
        ->and($membership->pantryId()->equals($pantryId))->toBeTrue()
        ->and($membership->userId()->equals($userId))->toBeTrue()
        ->and($membership->createdAt())->toBe($createdAt);
});
