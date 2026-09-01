<?php

use App\Infrastructure\Persistence\Eloquent\Models\EloquentMedia;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Database\Factories\RecipeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('includes the owner username and display name in the recipe response', function () {
    $owner = EloquentUser::factory()->create([
        'username' => 'chef_exemplo',
        'display_name' => 'Chef Exemplo',
    ]);
    $recipe = RecipeFactory::new()
        ->withIngredientsAndSteps()
        ->create(['user_id' => $owner->id]);

    $response = $this->getJson("/api/recipes/{$recipe->id}");

    $response->assertOk()
        ->assertJsonPath('ownerUsername', 'chef_exemplo')
        ->assertJsonPath('ownerDisplayName', 'Chef Exemplo');
});

it('includes signed cover URLs when the recipe has an approved cover media', function () {
    $owner = EloquentUser::factory()->create();
    $media = EloquentMedia::create([
        'id' => (string) Str::ulid(),
        'owner_user_id' => $owner->id,
        'kind' => 'recipe_photo',
        'storage_key' => (string) Str::ulid(),
        'focal_x' => 0.5,
        'focal_y' => 0.5,
        'width' => 800,
        'height' => 600,
        'status' => 'approved',
    ]);
    $recipe = RecipeFactory::new()
        ->withIngredientsAndSteps()
        ->create(['user_id' => $owner->id, 'cover_media_id' => $media->id]);

    $response = $this->getJson("/api/recipes/{$recipe->id}");

    $response->assertOk();
    expect($response->json('coverThumbnailUrl'))->toContain($media->storage_key);
    expect($response->json('coverDisplayUrl'))->toContain($media->storage_key);
});

it('returns null cover URLs when the recipe has no cover media', function () {
    $recipe = RecipeFactory::new()->withIngredientsAndSteps()->create();

    $response = $this->getJson("/api/recipes/{$recipe->id}");

    $response->assertOk()
        ->assertJsonPath('coverThumbnailUrl', null)
        ->assertJsonPath('coverDisplayUrl', null);
});

it('returns null cover URLs when the cover media was rejected by a moderator', function () {
    $owner = EloquentUser::factory()->create();
    $media = EloquentMedia::create([
        'id' => (string) Str::ulid(),
        'owner_user_id' => $owner->id,
        'kind' => 'recipe_photo',
        'storage_key' => (string) Str::ulid(),
        'focal_x' => 0.5,
        'focal_y' => 0.5,
        'width' => 800,
        'height' => 600,
        'status' => 'rejected',
        'rejection_reason' => 'inappropriate',
    ]);
    $recipe = RecipeFactory::new()
        ->withIngredientsAndSteps()
        ->create(['user_id' => $owner->id, 'cover_media_id' => $media->id]);

    $response = $this->getJson("/api/recipes/{$recipe->id}");

    $response->assertOk()
        ->assertJsonPath('coverThumbnailUrl', null)
        ->assertJsonPath('coverDisplayUrl', null);
});
