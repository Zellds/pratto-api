<?php

// tests/Feature/Application/User/EloquentUserRepositoryTest.php

use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\AccessTokenIssuerInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\DisplayName;
use App\Domain\User\Enums\UserRole;
use App\Domain\User\Enums\UserStatus;
use App\Domain\User\Exceptions\DuplicateUsernameException;
use App\Domain\User\Exceptions\GoogleAccountAlreadyLinkedException;
use App\Domain\User\User;
use App\Domain\User\Username;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser as EloquentUserModel;
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

it('registers a user with its password atomically', function () {
    $repository = new EloquentUserRepository;
    $user = User::register(Ulid::generate(), Username::fromString('nova'), DisplayName::fromString('Nova'));

    $repository->registerWithPassword($user, 'senha-forte-123');

    $found = $repository->verifyCredentials(Username::fromString('nova'), 'senha-forte-123');

    expect($found)->not->toBeNull()
        ->and($found->username()->value())->toBe('nova');
});

it('throws DuplicateUsernameException instead of a raw DB error on a unique-constraint race', function () {
    $repository = new EloquentUserRepository;
    $username = Username::fromString('corrida');

    $repository->registerWithPassword(
        User::register(Ulid::generate(), $username, DisplayName::fromString('Primeira')),
        'senha-forte-123',
    );

    expect(fn () => $repository->registerWithPassword(
        User::register(Ulid::generate(), $username, DisplayName::fromString('Segunda')),
        'outra-senha-123',
    ))->toThrow(DuplicateUsernameException::class);
});

it('finds a user by id', function () {
    $repository = app(UserRepositoryInterface::class);
    $registered = anOwner();

    $found = $repository->findById($registered);

    expect($found)->not->toBeNull()
        ->and($found->id()->equals($registered))->toBeTrue();
});

it('returns null when no user exists for that id', function () {
    $repository = app(UserRepositoryInterface::class);

    expect($repository->findById(Ulid::generate()))->toBeNull();
});

it('persists and hydrates role, status and ban fields exactly', function () {
    $repository = app(UserRepositoryInterface::class);
    $admin = anOwner();
    $user = User::register(Ulid::generate(), Username::fromString('gabriel_mod'), DisplayName::fromString('Gabriel'));
    $repository->save($user);

    $user->promoteToAdmin();
    $user->ban($admin, 'Motivo de teste.');
    $repository->save($user);

    $found = $repository->findByUsername(Username::fromString('gabriel_mod'));

    expect($found->role())->toBe(UserRole::Admin)
        ->and($found->status())->toBe(UserStatus::Banned)
        ->and($found->banReason())->toBe('Motivo de teste.')
        ->and($found->bannedBy()->equals($admin))->toBeTrue()
        ->and($found->bannedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('defaults to role user and status active for a freshly saved user', function () {
    $repository = app(UserRepositoryInterface::class);
    $user = User::register(Ulid::generate(), Username::fromString('gabriel_fresh'), DisplayName::fromString('Gabriel'));

    $repository->save($user);
    $found = $repository->findByUsername(Username::fromString('gabriel_fresh'));

    expect($found->role())->toBe(UserRole::User)
        ->and($found->status())->toBe(UserStatus::Active)
        ->and($found->isBanned())->toBeFalse();
});

it('revokes every access token for a user', function () {
    $issuer = app(AccessTokenIssuerInterface::class);
    $userId = anOwner();
    $issuer->issueFor($userId);
    $issuer->issueFor($userId);

    expect(EloquentUserModel::query()->find($userId->value())->tokens()->count())->toBe(2);

    $issuer->revokeAllFor($userId);

    expect(EloquentUserModel::query()->find($userId->value())->tokens()->count())->toBe(0);
});

it('persists and hydrates a user registered via google', function () {
    $repository = app(UserRepositoryInterface::class);
    $user = User::register(Ulid::generate(), Username::fromString('google_user_1'), DisplayName::fromString('Google User'));

    $repository->registerWithGoogle($user, 'google-sub-123', 'user@example.com');

    $found = $repository->findByGoogleId('google-sub-123');
    expect($found)->not->toBeNull()
        ->and($found->username()->value())->toBe('google_user_1');

    $byUsername = $repository->findByUsername(Username::fromString('google_user_1'));
    expect($byUsername->id()->equals($user->id()))->toBeTrue();
});

it('returns null for an unknown google id', function () {
    $repository = app(UserRepositoryInterface::class);

    expect($repository->findByGoogleId('does-not-exist'))->toBeNull();
});

it('throws DuplicateUsernameException when registering via google with a taken username', function () {
    $repository = app(UserRepositoryInterface::class);
    $existing = User::register(Ulid::generate(), Username::fromString('taken_name'), DisplayName::fromString('Existing'));
    $repository->registerWithGoogle($existing, 'google-sub-a', null);

    $newUser = User::register(Ulid::generate(), Username::fromString('taken_name'), DisplayName::fromString('New'));

    $repository->registerWithGoogle($newUser, 'google-sub-b', null);
})->throws(DuplicateUsernameException::class);

it('throws GoogleAccountAlreadyLinkedException when the google id is already registered', function () {
    $repository = app(UserRepositoryInterface::class);
    $existing = User::register(Ulid::generate(), Username::fromString('first_user'), DisplayName::fromString('First'));
    $repository->registerWithGoogle($existing, 'shared-google-id', null);

    $newUser = User::register(Ulid::generate(), Username::fromString('second_user'), DisplayName::fromString('Second'));

    $repository->registerWithGoogle($newUser, 'shared-google-id', null);
})->throws(GoogleAccountAlreadyLinkedException::class);

it('links a google id to an existing user', function () {
    $repository = app(UserRepositoryInterface::class);
    $owner = anOwner();

    $repository->linkGoogleId($owner, 'link-google-id', null);

    $found = $repository->findByGoogleId('link-google-id');
    expect($found->id()->equals($owner))->toBeTrue();
});

it('persists the email when linking a google id to an existing user', function () {
    $repository = app(UserRepositoryInterface::class);
    $owner = anOwner();

    $repository->linkGoogleId($owner, 'link-google-id-email', 'someone@example.com');

    $record = EloquentUserModel::query()->find($owner->value());
    expect($record->email)->toBe('someone@example.com');
});

it('throws GoogleAccountAlreadyLinkedException instead of a raw DB error when linking a google id already linked to another user', function () {
    $repository = app(UserRepositoryInterface::class);
    $ownerA = anOwner();
    $ownerB = anOwner();
    $repository->linkGoogleId($ownerA, 'race-link-google-id', null);

    expect(fn () => $repository->linkGoogleId($ownerB, 'race-link-google-id', null))
        ->toThrow(GoogleAccountAlreadyLinkedException::class);
});
