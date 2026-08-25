<?php

use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('returns the authenticated user profile', function () {
    $token = authenticatedToken($this);

    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/me');

    $response->assertOk()->assertJsonPath('username', 'gabriel');
});

it('updates the bio of the authenticated user', function () {
    $token = authenticatedToken($this);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/me', ['bio' => 'Cozinheiro amador.']);

    $response->assertOk()->assertJsonPath('bio', 'Cozinheiro amador.');
});

it('rejects unauthenticated access', function () {
    $this->getJson('/api/me')->assertStatus(401);
});

it('updates the avatar via profile update', function () {
    Storage::fake('media');
    $token = authenticatedToken($this);
    $ownerId = EloquentUser::query()->where('username', 'gabriel')->value('id');
    $avatar = anApprovedAvatar($ownerId);

    $response = $this->withToken($token)->patchJson('/api/me', ['bio' => 'Nova bio', 'avatar_media_id' => $avatar->id]);

    $response->assertOk()->assertJsonPath('avatarMediaId', $avatar->id);
});

it('rejects an avatar owned by someone else', function () {
    Storage::fake('media');
    $token = authenticatedToken($this);
    $strangerAvatar = anApprovedAvatar(anOwner()->value());

    $response = $this->withToken($token)->patchJson('/api/me', ['bio' => 'Nova bio', 'avatar_media_id' => $strangerAvatar->id]);

    $response->assertStatus(422);
});

it('keeps bio-only updates working without an avatar field', function () {
    $token = authenticatedToken($this);

    $response = $this->withToken($token)->patchJson('/api/me', ['bio' => 'Só bio']);

    $response->assertOk()->assertJsonPath('bio', 'Só bio');
});

it('returns a public profile by username without authentication', function () {
    authenticatedToken($this);

    $response = $this->getJson('/api/users/gabriel');

    $response->assertOk()
        ->assertJsonPath('username', 'gabriel')
        ->assertJsonPath('followersCount', 0)
        ->assertJsonPath('followingCount', 0);
});

it('returns 404 for a username that does not exist', function () {
    $response = $this->getJson('/api/users/nao_existe');

    $response->assertStatus(404);
});

it('returns 404 (not 500) for a malformed username', function () {
    $response = $this->getJson('/api/users/Gabriel');

    $response->assertStatus(404);
});

it('GET /me is an alias for the authenticated user\'s own public profile', function () {
    $token = authenticatedToken($this);

    $viaMe = $this->withToken($token)->getJson('/api/me');
    $viaUsername = $this->getJson('/api/users/gabriel');

    $viaMe->assertOk();
    $viaUsername->assertOk();
    expect($viaMe->json())->toBe($viaUsername->json());
});
