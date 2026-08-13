<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a recipe as draft', function () {
    $token = authenticatedToken($this);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/recipes', recipePayload());

    $response->assertCreated()->assertJsonPath('status', 'draft')->assertJsonPath('title', 'Bolo de cenoura');
});

it('rejects creation without authentication', function () {
    $this->postJson('/api/recipes', recipePayload())->assertStatus(401);
});

it('rejects creation with no ingredients', function () {
    $token = authenticatedToken($this);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/recipes', recipePayload(['ingredients' => []]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('ingredients');
});

it('updates a recipe and resets its status to draft', function () {
    $token = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/recipes', recipePayload())->json('id');
    $this->withHeader('Authorization', "Bearer {$token}")->postJson("/api/recipes/{$recipeId}/publish");

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/recipes/{$recipeId}", recipePayload(['title' => 'Bolo atualizado']));

    $response->assertOk()->assertJsonPath('title', 'Bolo atualizado')->assertJsonPath('status', 'draft');
});

it('forbids updating a recipe owned by someone else', function () {
    $token = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/recipes', recipePayload())->json('id');

    $otherToken = authenticatedTokenFor($this, 'outra');
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$otherToken}")
        ->patchJson("/api/recipes/{$recipeId}", recipePayload())
        ->assertStatus(403);
});

it('publishes a draft recipe', function () {
    $token = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/recipes', recipePayload())->json('id');

    $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson("/api/recipes/{$recipeId}/publish");

    $response->assertOk()->assertJsonPath('status', 'pending_review');
});

it('deletes a recipe (soft delete) and it stops being retrievable', function () {
    $token = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/recipes', recipePayload())->json('id');

    $this->withHeader('Authorization', "Bearer {$token}")->deleteJson("/api/recipes/{$recipeId}")->assertNoContent();

    $this->withHeader('Authorization', "Bearer {$token}")->getJson("/api/recipes/{$recipeId}")->assertStatus(404);
});
