<?php

use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\RegisterUser;
use App\Domain\User\DuplicateUsernameException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a new user and returns its profile', function () {
    $useCase = app(RegisterUser::class);

    $output = $useCase(new RegisterUserInput('gabriel', 'Gabriel Medeiros', 'senha-forte-123'));

    expect($output->username)->toBe('gabriel')
        ->and($output->displayName)->toBe('Gabriel Medeiros')
        ->and($output->bio)->toBeNull();
});

it('rejects a duplicate username', function () {
    $useCase = app(RegisterUser::class);
    $useCase(new RegisterUserInput('gabriel', 'Gabriel Medeiros', 'senha-forte-123'));

    $useCase(new RegisterUserInput('gabriel', 'Outro Nome', 'outra-senha-123'));
})->throws(DuplicateUsernameException::class);
