<?php

// tests/Feature/Http/PantryMemberEndpointTest.php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('invites a member by username', function () {
    $token = authenticatedToken($this);
    $created = $this->withToken($token)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();
    authenticatedTokenFor($this, 'invitee_user');

    $response = $this->withToken($token)->postJson("/api/pantries/{$created['id']}/members", ['username' => 'invitee_user']);

    $response->assertStatus(204);
});

it('is idempotent when inviting the same user twice', function () {
    $token = authenticatedToken($this);
    $created = $this->withToken($token)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();
    authenticatedTokenFor($this, 'invitee_user');

    $this->withToken($token)->postJson("/api/pantries/{$created['id']}/members", ['username' => 'invitee_user'])->assertStatus(204);
    $response = $this->withToken($token)->postJson("/api/pantries/{$created['id']}/members", ['username' => 'invitee_user']);

    $response->assertStatus(204);
});

it('rejects inviting yourself', function () {
    $token = authenticatedToken($this);
    $created = $this->withToken($token)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();

    $response = $this->withToken($token)->postJson("/api/pantries/{$created['id']}/members", ['username' => 'gabriel']);

    $response->assertStatus(422);
});

it('returns 404 for a non-existent invitee username', function () {
    $token = authenticatedToken($this);
    $created = $this->withToken($token)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();

    $response = $this->withToken($token)->postJson("/api/pantries/{$created['id']}/members", ['username' => 'nao_existe']);

    $response->assertStatus(404);
});

it('requires authentication to invite', function () {
    $pantry = aPantry(anOwner()->value());

    $response = $this->postJson("/api/pantries/{$pantry->id}/members", ['username' => 'gabriel']);

    $response->assertStatus(401);
});

it('lists members including the owner', function () {
    $token = authenticatedToken($this);
    $created = $this->withToken($token)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();

    $response = $this->withToken($token)->getJson("/api/pantries/{$created['id']}/members");

    $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.role', 'owner');
});

it('lets a member leave via the same delete route', function () {
    $ownerToken = authenticatedToken($this);
    $created = $this->withToken($ownerToken)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();
    $memberToken = authenticatedTokenFor($this, 'member_user');
    $this->withToken($ownerToken)->postJson("/api/pantries/{$created['id']}/members", ['username' => 'member_user']);
    $this->app['auth']->forgetGuards();

    $response = $this->withToken($memberToken)->deleteJson("/api/pantries/{$created['id']}/members/member_user");

    $response->assertStatus(204);
});

it('lets the owner remove another member via the same delete route', function () {
    $ownerToken = authenticatedToken($this);
    $created = $this->withToken($ownerToken)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();
    authenticatedTokenFor($this, 'member_user');
    $this->withToken($ownerToken)->postJson("/api/pantries/{$created['id']}/members", ['username' => 'member_user']);

    $response = $this->withToken($ownerToken)->deleteJson("/api/pantries/{$created['id']}/members/member_user");

    $response->assertStatus(204);
});

it('forbids a non-owner member from removing someone else', function () {
    $ownerToken = authenticatedToken($this);
    $created = $this->withToken($ownerToken)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();
    $memberAToken = authenticatedTokenFor($this, 'member_a');
    authenticatedTokenFor($this, 'member_b');
    $this->withToken($ownerToken)->postJson("/api/pantries/{$created['id']}/members", ['username' => 'member_a']);
    $this->withToken($ownerToken)->postJson("/api/pantries/{$created['id']}/members", ['username' => 'member_b']);
    $this->app['auth']->forgetGuards();

    $response = $this->withToken($memberAToken)->deleteJson("/api/pantries/{$created['id']}/members/member_b");

    $response->assertStatus(403);
});
