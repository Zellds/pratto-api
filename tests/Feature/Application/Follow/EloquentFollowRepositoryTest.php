<?php
// tests/Feature/Application/Follow/EloquentFollowRepositoryTest.php

use App\Domain\Follow\Contracts\FollowRepositoryInterface;
use App\Domain\Follow\Follow;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('saves a follow and reports it exists', function () {
    $repository = app(FollowRepositoryInterface::class);
    $follower = anOwner();
    $followee = anOwner();

    expect($repository->exists($follower, $followee))->toBeFalse();

    $repository->save(Follow::create(Ulid::generate(), $follower, $followee));

    expect($repository->exists($follower, $followee))->toBeTrue();
});

it('deletes a follow', function () {
    $repository = app(FollowRepositoryInterface::class);
    $follower = anOwner();
    $followee = anOwner();
    $repository->save(Follow::create(Ulid::generate(), $follower, $followee));

    $repository->delete($follower, $followee);

    expect($repository->exists($follower, $followee))->toBeFalse();
});

it('deleting a non-existent follow is a no-op', function () {
    $repository = app(FollowRepositoryInterface::class);

    $repository->delete(Ulid::generate(), Ulid::generate());

    expect(true)->toBeTrue();
});

it('counts followers and following separately', function () {
    $repository = app(FollowRepositoryInterface::class);
    $center = anOwner();
    $followerA = anOwner();
    $followerB = anOwner();
    $followee = anOwner();

    $repository->save(Follow::create(Ulid::generate(), $followerA, $center));
    $repository->save(Follow::create(Ulid::generate(), $followerB, $center));
    $repository->save(Follow::create(Ulid::generate(), $center, $followee));

    expect($repository->countFollowers($center))->toBe(2)
        ->and($repository->countFollowing($center))->toBe(1);
});

it('lists the ids of every user a given user follows', function () {
    $repository = app(FollowRepositoryInterface::class);
    $follower = anOwner();
    $followeeA = anOwner();
    $followeeB = anOwner();

    $repository->save(Follow::create(Ulid::generate(), $follower, $followeeA));
    $repository->save(Follow::create(Ulid::generate(), $follower, $followeeB));

    $ids = $repository->followeeIdsFor($follower);

    expect(collect($ids)->map(fn (Ulid $id) => $id->value())->sort()->values()->all())
        ->toBe(collect([$followeeA, $followeeB])->map(fn (Ulid $id) => $id->value())->sort()->values()->all());
});
