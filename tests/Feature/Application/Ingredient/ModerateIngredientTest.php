<?php

use App\Application\Ingredient\UseCases\ApproveIngredient;
use App\Application\Ingredient\UseCases\RejectIngredient;
use App\Domain\Ingredient\Contracts\IngredientRepositoryInterface;
use App\Domain\Ingredient\Enums\IngredientStatus;
use App\Domain\Ingredient\Exceptions\IngredientNotFoundException;
use App\Domain\Shared\Ulid;

it('approves a provisional ingredient', function () {
    $ingredientId = anIngredientId('Cebola roxa');

    $output = app(ApproveIngredient::class)($ingredientId->value());

    expect($output->status)->toBe('approved');

    $found = app(IngredientRepositoryInterface::class)->findById($ingredientId);
    expect($found->status())->toBe(IngredientStatus::Approved);
});

it('rejects a provisional ingredient', function () {
    $ingredientId = anIngredientId('Xyz inválido');

    $output = app(RejectIngredient::class)($ingredientId->value());

    expect($output->status)->toBe('rejected');

    $found = app(IngredientRepositoryInterface::class)->findById($ingredientId);
    expect($found->status())->toBe(IngredientStatus::Rejected);
});

it('throws IngredientNotFoundException when approving a non-existent ingredient', function () {
    app(ApproveIngredient::class)((string) Ulid::generate());
})->throws(IngredientNotFoundException::class);
