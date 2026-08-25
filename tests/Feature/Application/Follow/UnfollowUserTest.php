<?php

// tests/Feature/Application/Follow/UnfollowUserTest.php

use App\Application\Follow\UseCases\UnfollowUser;
use App\Domain\Follow\Contracts\FollowRepositoryInterface;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('unfollows a user by username', function () {
    $follower = anOwner();
    $followee = anOwner();
    $followeeUsername = EloquentUser::query()->whereKey($followee->value())->value('username');
    aFollow($follower->value(), $followee->value());

    app(UnfollowUser::class)($follower->value(), $followeeUsername);

    expect(app(FollowRepositoryInterface::class)->exists($follower, $followee))->toBeFalse();
});

it('is idempotent when unfollowing a user that was never followed', function () {
    $follower = anOwner();
    $followee = anOwner();
    $followeeUsername = EloquentUser::query()->whereKey($followee->value())->value('username');

    app(UnfollowUser::class)($follower->value(), $followeeUsername);

    expect(true)->toBeTrue();
});

it('throws when the followee username does not exist', function () {
    app(UnfollowUser::class)(anOwner()->value(), 'nao_existe');
})->throws(UserNotFoundException::class);
