<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets anyone read a pending_review recipe without authentication', function () {
    $token = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/recipes', recipePayload())->json('id');
    $this->withHeader('Authorization', "Bearer {$token}")->postJson("/api/recipes/{$recipeId}/publish");

    $this->app['auth']->forgetGuards();
    $this->withoutHeader('Authorization')->getJson("/api/recipes/{$recipeId}")->assertOk()->assertJsonPath('status', 'pending_review');
});

it('hides a draft recipe from the public and from other users', function () {
    $token = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/recipes', recipePayload())->json('id');

    $this->app['auth']->forgetGuards();
    $this->withoutHeader('Authorization')->getJson("/api/recipes/{$recipeId}")->assertStatus(404);

    $otherToken = authenticatedTokenFor($this, 'outra');
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$otherToken}")
        ->getJson("/api/recipes/{$recipeId}")->assertStatus(404);
});

it('lets the owner read their own draft', function () {
    $token = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/recipes', recipePayload())->json('id');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/recipes/{$recipeId}")->assertOk()->assertJsonPath('status', 'draft');
});

it('excludes drafts from public search but includes them with ?mine=true', function () {
    $token = authenticatedToken($this);
    $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/recipes', recipePayload());

    $this->app['auth']->forgetGuards();
    $this->withoutHeader('Authorization')->getJson('/api/recipes')->assertOk()->assertJsonCount(0);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/recipes?mine=1')->assertOk()->assertJsonCount(1);
});

it('rejects ?mine=true without authentication', function () {
    $this->getJson('/api/recipes?mine=1')->assertStatus(401);
});
