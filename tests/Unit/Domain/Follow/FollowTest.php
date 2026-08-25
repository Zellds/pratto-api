<?php
// tests/Unit/Domain/Follow/FollowTest.php

use App\Domain\Follow\Exceptions\CannotFollowSelfException;
use App\Domain\Follow\Follow;
use App\Domain\Shared\Ulid;

it('creates a follow from one user to another', function () {
    $follower = Ulid::generate();
    $followee = Ulid::generate();

    $follow = Follow::create(Ulid::generate(), $follower, $followee);

    expect($follow->followerId()->equals($follower))->toBeTrue()
        ->and($follow->followeeId()->equals($followee))->toBeTrue()
        ->and($follow->createdAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('rejects following yourself', function () {
    $userId = Ulid::generate();

    Follow::create(Ulid::generate(), $userId, $userId);
})->throws(CannotFollowSelfException::class);

it('reconstitutes with the same field order as create', function () {
    $id = Ulid::generate();
    $follower = Ulid::generate();
    $followee = Ulid::generate();
    $createdAt = new DateTimeImmutable('2026-01-01 12:00:00');

    $follow = Follow::reconstitute($id, $follower, $followee, $createdAt);

    expect($follow->id()->equals($id))->toBeTrue()
        ->and($follow->followerId()->equals($follower))->toBeTrue()
        ->and($follow->followeeId()->equals($followee))->toBeTrue()
        ->and($follow->createdAt())->toBe($createdAt);
});
