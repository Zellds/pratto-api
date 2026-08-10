<?php

use App\Application\Ingredient\DTOs\ResolveIngredientInput;
use App\Application\Ingredient\UseCases\ResolveIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('finds ingredients without authentication', function () {
    app(ResolveIngredient::class)(new ResolveIngredientInput(null, 'Tomate'));

    $response = $this->getJson('/api/ingredients?q=tomat');

    $response->assertOk()->assertJsonPath('0.name', 'Tomate');
});

it('rejects an empty search term', function () {
    $this->getJson('/api/ingredients')->assertStatus(422);
});
