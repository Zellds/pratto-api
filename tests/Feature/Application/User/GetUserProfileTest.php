<?php

use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\GetUserProfile;
use App\Application\User\UseCases\RegisterUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the profile of an existing user', function () {
    $registerUseCase = app(RegisterUser::class);
    $registerUseCase(new RegisterUserInput('gabriel', 'Gabriel Medeiros', 'senha-forte-123'));

    $useCase = app(GetUserProfile::class);

    $output = $useCase('gabriel');

    expect($output)->not->toBeNull()
        ->and($output->id)->toBeString()
        ->and($output->username)->toBe('gabriel')
        ->and($output->displayName)->toBe('Gabriel Medeiros')
        ->and($output->bio)->toBeNull();
});

it('returns null for a username that does not exist', function () {
    $useCase = app(GetUserProfile::class);

    $output = $useCase('nao_existe');

    expect($output)->toBeNull();
});
