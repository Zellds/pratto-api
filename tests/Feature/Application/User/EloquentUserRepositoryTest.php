<?php

// tests/Feature/Application/User/EloquentUserRepositoryTest.php

use App\Domain\Shared\Ulid;
use App\Domain\User\DisplayName;
use App\Domain\User\User;
use App\Domain\User\Username;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('saves a user and finds it back by username', function () {
    $repository = new EloquentUserRepository;
    $user = User::register(Ulid::generate(), Username::fromString('gabriel'), DisplayName::fromString('Gabriel'));

    $repository->save($user);
    $found = $repository->findByUsername(Username::fromString('gabriel'));

    expect($found)->not->toBeNull()
        ->and($found->username()->value())->toBe('gabriel')
        ->and($found->displayName()->value())->toBe('Gabriel');
});

it('returns null when username does not exist', function () {
    $repository = new EloquentUserRepository;

    expect($repository->findByUsername(Username::fromString('ninguem')))->toBeNull();
});
