<?php

use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets an admin approve a provisional ingredient', function () {
    $ingredientId = anIngredientId('Cebola caramelizada')->value();

    $adminToken = authenticatedTokenFor($this, 'ingredient_admin');
    EloquentUser::query()->where('username', 'ingredient_admin')->update(['role' => 'admin']);
    $this->app['auth']->forgetGuards();

    $response = $this->withHeader('Authorization', "Bearer {$adminToken}")->patchJson("/api/ingredients/{$ingredientId}/approve");

    $response->assertOk()->assertJsonPath('status', 'approved');
});

it('lets an admin reject a provisional ingredient', function () {
    $ingredientId = anIngredientId('Xyz inválido')->value();

    $adminToken = authenticatedTokenFor($this, 'ingredient_admin_2');
    EloquentUser::query()->where('username', 'ingredient_admin_2')->update(['role' => 'admin']);
    $this->app['auth']->forgetGuards();

    $response = $this->withHeader('Authorization', "Bearer {$adminToken}")->patchJson("/api/ingredients/{$ingredientId}/reject");

    $response->assertOk()->assertJsonPath('status', 'rejected');
});

it('forbids a non-admin from approving an ingredient', function () {
    $ingredientId = anIngredientId('Alecrim')->value();
    $token = authenticatedToken($this);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/ingredients/{$ingredientId}/approve")
        ->assertStatus(403);
});
