<?php

use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a pantry', function () {
    $token = authenticatedToken($this);

    $response = $this->withToken($token)->postJson('/api/pantries', ['name' => 'Minha despensa']);

    $response->assertStatus(201)
        ->assertJsonPath('name', 'Minha despensa')
        ->assertJsonPath('role', 'owner');
});

it('rejects creating a pantry past the limit', function () {
    $token = authenticatedToken($this);
    for ($i = 0; $i < 5; $i++) {
        $this->withToken($token)->postJson('/api/pantries', ['name' => "Despensa {$i}"]);
    }

    $response = $this->withToken($token)->postJson('/api/pantries', ['name' => 'Despensa demais']);

    $response->assertStatus(422);
});

it('requires authentication to create', function () {
    $response = $this->postJson('/api/pantries', ['name' => 'Minha despensa']);

    $response->assertStatus(401);
});

it('lists my pantries', function () {
    $token = authenticatedToken($this);
    $this->withToken($token)->postJson('/api/pantries', ['name' => 'Minha despensa']);

    $response = $this->withToken($token)->getJson('/api/pantries');

    $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.role', 'owner');
});

it('deletes a pantry owned by the actor', function () {
    $token = authenticatedToken($this);
    $created = $this->withToken($token)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();

    $response = $this->withToken($token)->deleteJson("/api/pantries/{$created['id']}");

    $response->assertStatus(204);
});

it('returns 404 deleting a pantry with no access', function () {
    $token = authenticatedToken($this);
    $pantry = aPantry(anOwner()->value());

    $response = $this->withToken($token)->deleteJson("/api/pantries/{$pantry->id}");

    $response->assertStatus(404);
});

it('returns 403 when a member (not the owner) tries to delete', function () {
    $ownerToken = authenticatedToken($this);
    $created = $this->withToken($ownerToken)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();
    $memberToken = authenticatedTokenFor($this, 'member_user');
    aPantryMember($created['id'], EloquentUser::query()->where('username', 'gabriel')->value('id'), 'member_user');
    $this->app['auth']->forgetGuards();

    $response = $this->withToken($memberToken)->deleteJson("/api/pantries/{$created['id']}");

    $response->assertStatus(403);
});

it('requires authentication to delete', function () {
    $pantry = aPantry(anOwner()->value());

    $response = $this->deleteJson("/api/pantries/{$pantry->id}");

    $response->assertStatus(401);
});
