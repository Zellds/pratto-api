<?php

// tests/Feature/Application/Follow/FollowUserTest.php

use App\Application\Follow\UseCases\FollowUser;
use App\Domain\Follow\Contracts\FollowRepositoryInterface;
use App\Domain\Follow\Exceptions\CannotFollowSelfException;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('follows a user by username', function () {
    $follower = anOwner();
    $followee = anOwner();
    $followeeUsername = EloquentUser::query()->whereKey($followee->value())->value('username');

    app(FollowUser::class)($follower->value(), $followeeUsername);

    expect(app(FollowRepositoryInterface::class)->exists($follower, $followee))->toBeTrue();
});

it('is idempotent when following the same user twice', function () {
    $follower = anOwner();
    $followee = anOwner();
    $followeeUsername = EloquentUser::query()->whereKey($followee->value())->value('username');

    app(FollowUser::class)($follower->value(), $followeeUsername);
    app(FollowUser::class)($follower->value(), $followeeUsername);

    expect(app(FollowRepositoryInterface::class)->countFollowers($followee))->toBe(1);
});

it('throws when the followee username does not exist', function () {
    app(FollowUser::class)(anOwner()->value(), 'nao_existe');
})->throws(UserNotFoundException::class);

it('throws when trying to follow yourself', function () {
    $user = anOwner();
    $username = EloquentUser::query()->whereKey($user->value())->value('username');

    app(FollowUser::class)($user->value(), $username);
})->throws(CannotFollowSelfException::class);

it('throws UserNotFoundException for a malformed followee username', function () {
    app(FollowUser::class)(anOwner()->value(), 'Gabriel');
})->throws(UserNotFoundException::class);
