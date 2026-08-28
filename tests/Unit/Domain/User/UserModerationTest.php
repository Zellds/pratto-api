<?php

use App\Domain\Shared\Ulid;
use App\Domain\User\DisplayName;
use App\Domain\User\Enums\UserRole;
use App\Domain\User\Enums\UserStatus;
use App\Domain\User\User;
use App\Domain\User\Username;

function aFreshUser(): User
{
    return User::register(Ulid::generate(), Username::fromString('gabriel'), DisplayName::fromString('Gabriel'));
}

it('registers with role user and status active by default', function () {
    $user = aFreshUser();

    expect($user->role())->toBe(UserRole::User)
        ->and($user->status())->toBe(UserStatus::Active)
        ->and($user->isBanned())->toBeFalse();
});

it('promotes to admin', function () {
    $user = aFreshUser();

    $user->promoteToAdmin();

    expect($user->role())->toBe(UserRole::Admin);
});

it('bans a user with a reason', function () {
    $admin = Ulid::generate();
    $user = aFreshUser();

    $user->ban($admin, 'Conteúdo impróprio repetido.');

    expect($user->isBanned())->toBeTrue()
        ->and($user->status())->toBe(UserStatus::Banned)
        ->and($user->banReason())->toBe('Conteúdo impróprio repetido.')
        ->and($user->bannedBy()->equals($admin))->toBeTrue()
        ->and($user->bannedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('rejects an empty ban reason', function () {
    aFreshUser()->ban(Ulid::generate(), '   ');
})->throws(InvalidArgumentException::class);

it('unbans a user, clearing every ban field', function () {
    $user = aFreshUser();
    $user->ban(Ulid::generate(), 'Motivo.');

    $user->unban();

    expect($user->isBanned())->toBeFalse()
        ->and($user->status())->toBe(UserStatus::Active)
        ->and($user->banReason())->toBeNull()
        ->and($user->bannedAt())->toBeNull()
        ->and($user->bannedBy())->toBeNull();
});

it('restores the exact moderation state from a hydration call, preserving the original bannedAt', function () {
    $user = aFreshUser();
    $bannedBy = Ulid::generate();
    $bannedAt = new DateTimeImmutable('2026-01-01 10:00:00');

    $user->restoreModerationState(UserRole::Admin, UserStatus::Banned, $bannedAt, 'Motivo antigo.', $bannedBy);

    expect($user->role())->toBe(UserRole::Admin)
        ->and($user->status())->toBe(UserStatus::Banned)
        ->and($user->bannedAt())->toBe($bannedAt)
        ->and($user->banReason())->toBe('Motivo antigo.')
        ->and($user->bannedBy()->equals($bannedBy))->toBeTrue();
});
