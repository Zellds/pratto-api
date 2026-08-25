<?php

use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\GetUserProfile;
use App\Application\User\UseCases\RegisterUser;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
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

it('includes followersCount and followingCount', function () {
    $registerUseCase = app(RegisterUser::class);
    $registerUseCase(new RegisterUserInput('gabriel', 'Gabriel Medeiros', 'senha-forte-123'));
    $gabrielId = EloquentUser::query()->where('username', 'gabriel')->value('id');

    $followerA = anOwner();
    $followerB = anOwner();
    aFollow($followerA->value(), $gabrielId);
    aFollow($followerB->value(), $gabrielId);

    $followedByGabriel = anOwner();
    aFollow($gabrielId, $followedByGabriel->value());

    $output = app(GetUserProfile::class)('gabriel');

    expect($output->followersCount)->toBe(2)
        ->and($output->followingCount)->toBe(1);
});

it('returns zero counts for a user nobody follows', function () {
    $registerUseCase = app(RegisterUser::class);
    $registerUseCase(new RegisterUserInput('gabriel', 'Gabriel Medeiros', 'senha-forte-123'));

    $output = app(GetUserProfile::class)('gabriel');

    expect($output->followersCount)->toBe(0)
        ->and($output->followingCount)->toBe(0);
});
