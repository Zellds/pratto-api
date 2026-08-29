<?php
// tests/Feature/Application/User/LinkGoogleAccountTest.php

use App\Application\User\UseCases\LinkGoogleAccount;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Exceptions\GoogleAccountAlreadyLinkedException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('links a google account to the authenticated user', function () {
    $owner = anOwner();
    fakeGoogleVerifier(aGoogleIdentity(googleId: 'link-1'));

    app(LinkGoogleAccount::class)($owner->value(), 'some-token');

    $found = app(UserRepositoryInterface::class)->findByGoogleId('link-1');
    expect($found->id()->equals($owner))->toBeTrue();
});

it('allows relinking the same google account to the same user without error', function () {
    $owner = anOwner();
    fakeGoogleVerifier(aGoogleIdentity(googleId: 'link-2'));
    app(LinkGoogleAccount::class)($owner->value(), 'token-a');

    app(LinkGoogleAccount::class)($owner->value(), 'token-a');

    expect(app(UserRepositoryInterface::class)->findByGoogleId('link-2')->id()->equals($owner))->toBeTrue();
});

it('throws when the google account is already linked to a different user', function () {
    $ownerA = anOwner();
    $ownerB = anOwner();
    fakeGoogleVerifier(aGoogleIdentity(googleId: 'link-3'));
    app(LinkGoogleAccount::class)($ownerA->value(), 'token-a');

    app(LinkGoogleAccount::class)($ownerB->value(), 'token-b');
})->throws(GoogleAccountAlreadyLinkedException::class);
