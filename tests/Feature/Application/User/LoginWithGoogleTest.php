<?php

use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\BanUser;
use App\Application\User\UseCases\LoginWithGoogle;
use App\Application\User\UseCases\RegisterUser;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Exceptions\InvalidGoogleTokenException;
use App\Domain\User\Exceptions\UserBannedException;
use App\Domain\User\Username;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a new user on the first login via google', function () {
    fakeGoogleVerifier(aGoogleIdentity(name: 'Jane Doe', email: 'jane@example.com'));

    $output = app(LoginWithGoogle::class)('any-id-token');

    expect($output->token)->not->toBeEmpty();
    $user = app(UserRepositoryInterface::class)->findByUsername(Username::fromString('jane_doe'));
    expect($user)->not->toBeNull();
});

it('logs in an existing google user without creating a duplicate account', function () {
    $identity = aGoogleIdentity(name: 'Returning User');
    fakeGoogleVerifier($identity);
    app(LoginWithGoogle::class)('token-1');

    $output = app(LoginWithGoogle::class)('token-1');

    expect($output->token)->not->toBeEmpty();
    expect(EloquentUser::query()->where('google_id', $identity->googleId)->count())->toBe(1);
});

it('resolves a username collision by appending a numeric suffix', function () {
    app(RegisterUser::class)(new RegisterUserInput('jane_doe', 'Existing Jane', 'senha-forte-123'));
    fakeGoogleVerifier(aGoogleIdentity(name: 'Jane Doe'));

    app(LoginWithGoogle::class)('some-token');

    expect(app(UserRepositoryInterface::class)->findByUsername(Username::fromString('jane_doe_2')))->not->toBeNull();
});

it('blocks a banned user from logging in via google', function () {
    $identity = aGoogleIdentity(name: 'Banned User');
    fakeGoogleVerifier($identity);
    app(LoginWithGoogle::class)('token-1');
    $user = app(UserRepositoryInterface::class)->findByGoogleId($identity->googleId);
    $admin = anAdmin();
    app(BanUser::class)($user->username()->value(), $admin->value(), 'Motivo.');

    app(LoginWithGoogle::class)('token-1');
})->throws(UserBannedException::class);

it('throws InvalidGoogleTokenException for an invalid token', function () {
    fakeInvalidGoogleVerifier();

    app(LoginWithGoogle::class)('bad-token');
})->throws(InvalidGoogleTokenException::class);
