<?php

use App\Domain\Follow\Contracts\FollowRepositoryInterface;
use App\Domain\Shared\Ulid;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('follows a user', function () {
    $followerToken = authenticatedToken($this);
    $followeeToken = authenticatedTokenFor($this, 'followee_user');

    $response = $this->withToken($followerToken)->postJson('/api/users/followee_user/follow');

    $response->assertStatus(204);
});

it('is idempotent when following the same user twice', function () {
    $followerToken = authenticatedToken($this);
    authenticatedTokenFor($this, 'followee_user');

    $this->withToken($followerToken)->postJson('/api/users/followee_user/follow')->assertStatus(204);
    $this->withToken($followerToken)->postJson('/api/users/followee_user/follow')->assertStatus(204);

    $followerId = EloquentUser::query()->where('username', 'gabriel')->value('id');
    $followeeId = EloquentUser::query()->where('username', 'followee_user')->value('id');

    expect(app(FollowRepositoryInterface::class)->exists(Ulid::fromString($followerId), Ulid::fromString($followeeId)))->toBeTrue();
});

it('rejects following yourself', function () {
    $token = authenticatedToken($this);

    $response = $this->withToken($token)->postJson('/api/users/gabriel/follow');

    $response->assertStatus(422);
});

it('returns 404 for a non-existent username', function () {
    $token = authenticatedToken($this);

    $response = $this->withToken($token)->postJson('/api/users/nao_existe/follow');

    $response->assertStatus(404);
});

it('requires authentication to follow', function () {
    authenticatedTokenFor($this, 'followee_user');

    $response = $this->postJson('/api/users/followee_user/follow');

    $response->assertStatus(401);
});

it('unfollows a user', function () {
    $followerToken = authenticatedToken($this);
    authenticatedTokenFor($this, 'followee_user');
    $this->withToken($followerToken)->postJson('/api/users/followee_user/follow');

    $response = $this->withToken($followerToken)->deleteJson('/api/users/followee_user/follow');

    $response->assertStatus(204);
});

it('is idempotent when unfollowing a user that was never followed', function () {
    $followerToken = authenticatedToken($this);
    authenticatedTokenFor($this, 'followee_user');

    $response = $this->withToken($followerToken)->deleteJson('/api/users/followee_user/follow');

    $response->assertStatus(204);
});

it('requires authentication to unfollow', function () {
    authenticatedTokenFor($this, 'followee_user');

    $response = $this->deleteJson('/api/users/followee_user/follow');

    $response->assertStatus(401);
});
