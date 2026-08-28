<?php

// tests/Feature/Http/Recipe/RecipeModerationEndpointTest.php

use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

function anAdminToken(TestCase $test): string
{
    $token = authenticatedTokenFor($test, 'recipe_admin');
    EloquentUser::query()->where('username', 'recipe_admin')->update(['role' => 'admin']);

    return $token;
}

it('lets an admin approve a pending review recipe', function () {
    $ownerToken = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson('/api/recipes', recipePayload())->json('id');
    $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson("/api/recipes/{$recipeId}/publish");

    $adminToken = anAdminToken($this);
    $this->app['auth']->forgetGuards();

    $response = $this->withHeader('Authorization', "Bearer {$adminToken}")->patchJson("/api/recipes/{$recipeId}/approve");

    $response->assertOk()->assertJsonPath('status', 'published');
});

it('lets an admin reject a pending review recipe with a reason, hiding it from the public but not the owner', function () {
    $ownerToken = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson('/api/recipes', recipePayload())->json('id');
    $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson("/api/recipes/{$recipeId}/publish");

    $adminToken = anAdminToken($this);
    $this->app['auth']->forgetGuards();

    $response = $this->withHeader('Authorization', "Bearer {$adminToken}")
        ->patchJson("/api/recipes/{$recipeId}/reject", ['reason' => 'Foto imprópria.']);
    $response->assertOk()->assertJsonPath('status', 'rejected')->assertJsonPath('rejectionReason', 'Foto imprópria.');

    $this->app['auth']->forgetGuards();
    $this->withoutHeader('Authorization')->getJson("/api/recipes/{$recipeId}")->assertStatus(404);

    $this->app['auth']->forgetGuards();
    $this->withHeader('Authorization', "Bearer {$ownerToken}")
        ->getJson("/api/recipes/{$recipeId}")
        ->assertOk()
        ->assertJsonPath('rejectionReason', 'Foto imprópria.');
});

it('forbids a non-admin from approving a recipe', function () {
    $ownerToken = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson('/api/recipes', recipePayload())->json('id');
    $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson("/api/recipes/{$recipeId}/publish");

    $this->withHeader('Authorization', "Bearer {$ownerToken}")
        ->patchJson("/api/recipes/{$recipeId}/approve")
        ->assertStatus(403);
});

it('rejects an empty reason with a validation error', function () {
    $ownerToken = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson('/api/recipes', recipePayload())->json('id');
    $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson("/api/recipes/{$recipeId}/publish");

    $adminToken = anAdminToken($this);
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$adminToken}")
        ->patchJson("/api/recipes/{$recipeId}/reject", ['reason' => ''])
        ->assertStatus(422);
});
