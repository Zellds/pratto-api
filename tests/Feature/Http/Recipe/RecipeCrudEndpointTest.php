<?php

use App\Application\Rating\UseCases\RateRecipe;
use App\Application\Recipe\UseCases\PublishRecipe;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

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

it('rejects publishing an already-published recipe with a conflict', function () {
    $token = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/recipes', recipePayload())->json('id');
    $this->withHeader('Authorization', "Bearer {$token}")->postJson("/api/recipes/{$recipeId}/publish")->assertOk();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/recipes/{$recipeId}/publish")
        ->assertStatus(409);
});

it('scales ingredient quantities when reading a recipe with ?portions', function () {
    $token = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/recipes', recipePayload([
            'portions' => 4,
            'ingredients' => [
                ['ingredient_name' => 'Farinha', 'quantity' => 2.0, 'unit' => 'g', 'position' => 0],
            ],
        ]))->json('id');
    $this->withHeader('Authorization', "Bearer {$token}")->postJson("/api/recipes/{$recipeId}/publish");

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/recipes/{$recipeId}?portions=8");

    $response->assertOk()->assertJsonPath('ingredients.0.quantity', 4);
});

it('finds recipes by full-text search on ?q', function () {
    $token = authenticatedToken($this);
    $chocolateId = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/recipes', recipePayload(['title' => 'Bolo de chocolate']))->json('id');
    $saltyId = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/recipes', recipePayload(['title' => 'Torta salgada']))->json('id');
    $this->withHeader('Authorization', "Bearer {$token}")->postJson("/api/recipes/{$chocolateId}/publish");
    $this->withHeader('Authorization', "Bearer {$token}")->postJson("/api/recipes/{$saltyId}/publish");

    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/recipes?q=chocolate');

    $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.title', 'Bolo de chocolate');
});

it('deletes a recipe (soft delete) and it stops being retrievable', function () {
    $token = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/recipes', recipePayload())->json('id');

    $this->withHeader('Authorization', "Bearer {$token}")->deleteJson("/api/recipes/{$recipeId}")->assertNoContent();

    $this->withHeader('Authorization', "Bearer {$token}")->getJson("/api/recipes/{$recipeId}")->assertStatus(404);
});

it('creates a recipe with a cover media owned by the user', function () {
    Storage::fake('media');
    $token = authenticatedToken($this);
    $ownerId = EloquentUser::query()->where('username', 'gabriel')->value('id');
    $cover = aPendingRecipePhoto($ownerId);

    $response = $this->withToken($token)->postJson('/api/recipes', recipePayload(['cover_media_id' => $cover->id]));

    $response->assertCreated()->assertJsonPath('coverMediaId', $cover->id);
});

it('rejects a cover media owned by another user', function () {
    Storage::fake('media');
    $token = authenticatedToken($this);
    $strangerCover = aPendingRecipePhoto(anOwner()->value());

    $response = $this->withToken($token)->postJson('/api/recipes', recipePayload(['cover_media_id' => $strangerCover->id]));

    $response->assertStatus(422);
});

it('rejects an avatar-kind media used as a recipe cover', function () {
    Storage::fake('media');
    $token = authenticatedToken($this);
    $ownerId = EloquentUser::query()->where('username', 'gabriel')->value('id');
    $avatar = anApprovedAvatar($ownerId);

    $response = $this->withToken($token)->postJson('/api/recipes', recipePayload(['cover_media_id' => $avatar->id]));

    $response->assertStatus(422);
});

it('keeps the cover media when updating a recipe without resending cover_media_id', function () {
    Storage::fake('media');
    $token = authenticatedToken($this);
    $ownerId = EloquentUser::query()->where('username', 'gabriel')->value('id');
    $cover = aPendingRecipePhoto($ownerId);
    $recipeId = $this->withToken($token)->postJson('/api/recipes', recipePayload(['cover_media_id' => $cover->id]))->json('id');

    $response = $this->withToken($token)->patchJson("/api/recipes/{$recipeId}", recipePayload(['title' => 'Bolo atualizado']));

    $response->assertOk()->assertJsonPath('coverMediaId', $cover->id);
});

it('exposes averageRating and ratingsCount on the recipe response', function () {
    $token = authenticatedToken($this);
    $ownerId = EloquentUser::query()->where('username', 'gabriel')->value('id');
    $draft = createADraft($ownerId);
    app(PublishRecipe::class)($draft->id, $ownerId);
    app(RateRecipe::class)($draft->id, anOwner()->value(), 4.0);

    $response = $this->withToken($token)->getJson("/api/recipes/{$draft->id}");

    // averageRating is 4.0 (a float) at the DTO level; PHP's json_encode collapses
    // whole-number floats to ints on the wire (no JSON_PRESERVE_ZERO_FRACTION), so the
    // decoded JSON value is int 4 — matching the pre-existing convention for the
    // 'ingredients.0.quantity' assertion above in this same file.
    $response->assertOk()
        ->assertJsonPath('averageRating', 4)
        ->assertJsonPath('ratingsCount', 1);
});

it('rounds averageRating to 1 decimal place on the recipe response', function () {
    $token = authenticatedToken($this);
    $ownerId = EloquentUser::query()->where('username', 'gabriel')->value('id');
    $draft = createADraft($ownerId);
    app(PublishRecipe::class)($draft->id, $ownerId);
    app(RateRecipe::class)($draft->id, anOwner()->value(), 4.0);
    app(RateRecipe::class)($draft->id, anOwner()->value(), 4.5);
    app(RateRecipe::class)($draft->id, anOwner()->value(), 4.5);

    $response = $this->withToken($token)->getJson("/api/recipes/{$draft->id}");

    // Raw average of 4.0, 4.5, 4.5 is 4.333333333333333 — RecipeResource rounds it
    // to 1 decimal place at HTTP serialization, so the response shows 4.3.
    $response->assertOk()->assertJsonPath('averageRating', 4.3);
});

it('round-trips is_optional per ingredient through the API', function () {
    $token = authenticatedToken($this);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/recipes', recipePayload([
            'ingredients' => [
                ['ingredient_name' => 'Cenoura', 'quantity' => 3, 'unit' => 'unidade', 'position' => 0],
                ['ingredient_name' => 'Leite', 'quantity' => 1, 'unit' => 'xicara', 'position' => 1, 'is_optional' => true],
            ],
        ]));

    $response->assertCreated()
        ->assertJsonPath('ingredients.0.isOptional', false)
        ->assertJsonPath('ingredients.1.isOptional', true);
});

it('defaults is_optional to false when omitted', function () {
    $token = authenticatedToken($this);

    $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/recipes', recipePayload());

    $response->assertCreated()->assertJsonPath('ingredients.0.isOptional', false);
});

it('persists is_optional to the database and reloads it correctly on a fresh read', function () {
    $token = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/recipes', recipePayload([
            'ingredients' => [
                ['ingredient_name' => 'Cenoura', 'quantity' => 3, 'unit' => 'unidade', 'position' => 0],
                ['ingredient_name' => 'Leite', 'quantity' => 1, 'unit' => 'xicara', 'position' => 1, 'is_optional' => true],
            ],
        ]))->json('id');

    // The create response is built from the in-memory domain object (before persisting),
    // so it doesn't prove the flag survives a save/reload cycle. This GET forces a fresh
    // read from the database — through the Eloquent model and toDomain() hydration — to
    // confirm is_optional round-trips correctly in both directions.
    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson("/api/recipes/{$recipeId}");

    $response->assertOk()
        ->assertJsonPath('ingredients.0.isOptional', false)
        ->assertJsonPath('ingredients.1.isOptional', true);
});

it('returns the ingredient name on each recipe line when reading a recipe back', function () {
    $token = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/recipes', recipePayload())->json('id');

    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson("/api/recipes/{$recipeId}");

    $response->assertOk()->assertJsonPath('ingredients.0.ingredientName', 'Cenoura');
});

it('resolves the right ingredient name for each line and preserves their order', function () {
    $token = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/recipes', recipePayload([
            'ingredients' => [
                ['ingredient_name' => 'Cenoura', 'quantity' => 3, 'unit' => 'unidade', 'position' => 0],
                ['ingredient_name' => 'Leite', 'quantity' => 1, 'unit' => 'xicara', 'position' => 1],
            ],
        ]))->json('id');

    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson("/api/recipes/{$recipeId}");

    $response->assertOk()
        ->assertJsonPath('ingredients.0.ingredientName', 'Cenoura')
        ->assertJsonPath('ingredients.1.ingredientName', 'Leite');
});

it('returns the ingredient name on the lines of recipes in the list', function () {
    $token = authenticatedToken($this);
    $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/recipes', recipePayload());

    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/recipes?mine=1');

    $response->assertOk()->assertJsonPath('0.ingredients.0.ingredientName', 'Cenoura');
});
