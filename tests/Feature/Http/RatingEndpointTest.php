<?php

use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Uid\Ulid;

uses(RefreshDatabase::class);

it('rates a recipe', function () {
    $ownerToken = authenticatedToken($this);
    $ownerId = EloquentUser::query()->where('username', 'gabriel')->value('id');
    $recipe = createAPendingReviewRecipe($ownerId);

    $raterToken = authenticatedTokenFor($this, 'rater_user');

    $response = $this->withToken($raterToken)->putJson("/api/recipes/{$recipe->id}/rating", ['score' => 4.5]);

    $response->assertOk()
        ->assertJsonPath('score', 4.5)
        ->assertJsonPath('recipeId', $recipe->id);
});

it('rejects a score outside the allowed half-step values', function () {
    $ownerToken = authenticatedToken($this);
    $ownerId = EloquentUser::query()->where('username', 'gabriel')->value('id');
    $recipe = createAPendingReviewRecipe($ownerId);

    $response = $this->withToken($ownerToken)->putJson("/api/recipes/{$recipe->id}/rating", ['score' => 3.2]);

    $response->assertStatus(422);
});

it('returns 404 for a non-existent recipe', function () {
    $token = authenticatedToken($this);

    $response = $this->withToken($token)->putJson('/api/recipes/'.(string) new Ulid.'/rating', ['score' => 4.0]);

    $response->assertStatus(404);
});

it('requires authentication', function () {
    $ownerId = anOwner()->value();
    $recipe = createAPendingReviewRecipe($ownerId);

    $response = $this->putJson("/api/recipes/{$recipe->id}/rating", ['score' => 4.0]);

    $response->assertStatus(401);
});
