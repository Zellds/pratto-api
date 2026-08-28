<?php

// tests/Feature/Application/User/ModerateUserTest.php

use App\Application\User\UseCases\BanUser;
use App\Application\User\UseCases\PromoteToAdmin;
use App\Application\User\UseCases\UnbanUser;
use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\AccessTokenIssuerInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Exceptions\UserNotFoundException;

function usernameOf(Ulid $id): string
{
    return app(UserRepositoryInterface::class)->findById($id)->username()->value();
}

it('promotes a user to admin', function () {
    $userId = anOwner();
    $username = usernameOf($userId);

    $output = app(PromoteToAdmin::class)($username);

    expect($output->id)->toBe($userId->value());
    expect(app(UserRepositoryInterface::class)->findById($userId)->role()->value)->toBe('admin');
});

it('bans a user and revokes every access token', function () {
    $adminId = anAdmin();
    $userId = anOwner();
    $username = usernameOf($userId);
    $issuer = app(AccessTokenIssuerInterface::class);
    $token = $issuer->issueFor($userId);

    app(BanUser::class)($username, $adminId->value(), 'Conteúdo impróprio repetido.');

    $found = app(UserRepositoryInterface::class)->findById($userId);
    expect($found->isBanned())->toBeTrue()
        ->and($found->banReason())->toBe('Conteúdo impróprio repetido.')
        ->and($found->bannedBy()->equals($adminId))->toBeTrue();

    // Sanctum's request guard caches the resolved user for the lifetime of the
    // application instance. Without forgetting it here, the next request reusing
    // this same container would still see the just-revoked token as authenticated.
    $this->app->forgetInstance('auth');

    $me = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/me');
    $me->assertStatus(401);
});

it('unbans a user', function () {
    $adminId = anAdmin();
    $userId = anOwner();
    $username = usernameOf($userId);
    app(BanUser::class)($username, $adminId->value(), 'Motivo.');

    app(UnbanUser::class)($username);

    expect(app(UserRepositoryInterface::class)->findById($userId)->isBanned())->toBeFalse();
});

it('throws UserNotFoundException when banning a non-existent username', function () {
    $adminId = anAdmin();

    app(BanUser::class)('ninguem', $adminId->value(), 'Motivo.');
})->throws(UserNotFoundException::class);
