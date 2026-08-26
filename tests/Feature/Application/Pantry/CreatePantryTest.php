<?php

// tests/Feature/Application/Pantry/CreatePantryTest.php

use App\Application\Pantry\UseCases\CreatePantry;
use App\Domain\Pantry\Exceptions\PantryLimitExceededException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a pantry owned by the given user', function () {
    $owner = anOwner();

    $output = app(CreatePantry::class)($owner->value(), 'Minha despensa');

    expect($output->ownerId)->toBe($owner->value())
        ->and($output->name)->toBe('Minha despensa')
        ->and($output->role)->toBe('owner');
});

it('throws when the user already has the maximum number of pantries', function () {
    $owner = anOwner();
    for ($i = 0; $i < 5; $i++) {
        aPantry($owner->value(), "Despensa {$i}");
    }

    app(CreatePantry::class)($owner->value(), 'Despensa demais');
})->throws(PantryLimitExceededException::class);
