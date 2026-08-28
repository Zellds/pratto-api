<?php

use App\Domain\User\GoogleIdentity;

it('builds a google identity with the given fields', function () {
    $identity = new GoogleIdentity('12345', 'user@example.com', 'Jane Doe');

    expect($identity->googleId)->toBe('12345')
        ->and($identity->email)->toBe('user@example.com')
        ->and($identity->name)->toBe('Jane Doe');
});

it('allows a null email', function () {
    $identity = new GoogleIdentity('12345', null, 'Jane Doe');

    expect($identity->email)->toBeNull();
});
