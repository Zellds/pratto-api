<?php

// tests/Feature/Http/PantryItemEndpointTest.php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('adds an item to a pantry', function () {
    $token = authenticatedToken($this);
    $pantry = $this->withToken($token)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();

    $response = $this->withToken($token)->postJson("/api/pantries/{$pantry['id']}/items", [
        'ingredient_name' => 'Cebola',
        'quantity' => 3,
        'unit' => 'unidade',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('quantity', 3.0)
        ->assertJsonPath('needsToBuy', true);
});

it('defaults quantity to 1.0 and unit to unidade when omitted', function () {
    $token = authenticatedToken($this);
    $pantry = $this->withToken($token)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();

    $response = $this->withToken($token)->postJson("/api/pantries/{$pantry['id']}/items", ['ingredient_name' => 'Sal']);

    $response->assertStatus(201)
        ->assertJsonPath('quantity', 1.0)
        ->assertJsonPath('unit', 'unidade');
});

it('returns 404 adding an item to a pantry with no access', function () {
    $token = authenticatedToken($this);
    $pantry = aPantry(anOwner()->value());

    $response = $this->withToken($token)->postJson("/api/pantries/{$pantry->id}/items", ['ingredient_name' => 'Cebola']);

    $response->assertStatus(404);
});

it('lists items in a pantry', function () {
    $token = authenticatedToken($this);
    $pantry = $this->withToken($token)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();
    $this->withToken($token)->postJson("/api/pantries/{$pantry['id']}/items", ['ingredient_name' => 'Cebola']);

    $response = $this->withToken($token)->getJson("/api/pantries/{$pantry['id']}/items");

    $response->assertOk()->assertJsonCount(1);
});

it('updates needsToBuy on an item', function () {
    $token = authenticatedToken($this);
    $pantry = $this->withToken($token)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();
    $item = $this->withToken($token)->postJson("/api/pantries/{$pantry['id']}/items", ['ingredient_name' => 'Cebola'])->json();

    $response = $this->withToken($token)->patchJson("/api/pantries/{$pantry['id']}/items/{$item['id']}", ['needs_to_buy' => false]);

    $response->assertOk()->assertJsonPath('needsToBuy', false);
});

it('returns 404 updating an item with no access to its pantry', function () {
    $token = authenticatedToken($this);
    $otherOwnerToken = authenticatedTokenFor($this, 'other_owner');
    $pantry = $this->withToken($otherOwnerToken)->postJson('/api/pantries', ['name' => 'De outro dono'])->json();
    $item = $this->withToken($otherOwnerToken)->postJson("/api/pantries/{$pantry['id']}/items", ['ingredient_name' => 'Cebola'])->json();
    $this->app['auth']->forgetGuards();

    $response = $this->withToken($token)->patchJson("/api/pantries/{$pantry['id']}/items/{$item['id']}", ['needs_to_buy' => false]);

    $response->assertStatus(404);
});

it('deletes an item', function () {
    $token = authenticatedToken($this);
    $pantry = $this->withToken($token)->postJson('/api/pantries', ['name' => 'Minha despensa'])->json();
    $item = $this->withToken($token)->postJson("/api/pantries/{$pantry['id']}/items", ['ingredient_name' => 'Cebola'])->json();

    $response = $this->withToken($token)->deleteJson("/api/pantries/{$pantry['id']}/items/{$item['id']}");

    $response->assertStatus(204);
});

it('requires authentication for every item route', function () {
    $pantry = aPantry(anOwner()->value());

    $this->postJson("/api/pantries/{$pantry->id}/items", ['ingredient_name' => 'Cebola'])->assertStatus(401);
    $this->getJson("/api/pantries/{$pantry->id}/items")->assertStatus(401);
});
