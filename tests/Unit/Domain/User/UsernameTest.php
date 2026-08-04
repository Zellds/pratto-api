<?php

use App\Domain\User\Username;

it('accepts a valid username', function () {
    $username = Username::fromString('gabriel_medeiros');

    expect($username->value())->toBe('gabriel_medeiros');
});

it('rejects usernames shorter than 3 characters', function () {
    Username::fromString('ab');
})->throws(InvalidArgumentException::class);

it('rejects usernames with spaces', function () {
    Username::fromString('gabriel medeiros');
})->throws(InvalidArgumentException::class);
