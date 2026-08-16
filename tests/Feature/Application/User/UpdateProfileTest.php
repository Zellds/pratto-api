<?php

use App\Application\User\UseCases\UpdateProfile;
use App\Domain\User\Exceptions\AvatarMediaNotOwnedException;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sets an avatar owned by the same user', function () {
    $owner = anOwner();
    $username = EloquentUser::query()->find($owner->value())->username;
    $avatar = anApprovedAvatar($owner->value());

    $profile = app(UpdateProfile::class)($username, 'Minha bio', $avatar->id, true);

    expect($profile->avatarMediaId)->toBe($avatar->id);
});

it('rejects an avatar owned by someone else', function () {
    $owner = anOwner();
    $username = EloquentUser::query()->find($owner->value())->username;
    $strangerAvatar = anApprovedAvatar(anOwner()->value());

    app(UpdateProfile::class)($username, 'Minha bio', $strangerAvatar->id, true);
})->throws(AvatarMediaNotOwnedException::class);

it('leaves the avatar untouched when the field is not provided', function () {
    $owner = anOwner();
    $username = EloquentUser::query()->find($owner->value())->username;
    $avatar = anApprovedAvatar($owner->value());
    app(UpdateProfile::class)($username, 'Bio inicial', $avatar->id, true);

    $profile = app(UpdateProfile::class)($username, 'Bio atualizada', null, false);

    expect($profile->avatarMediaId)->toBe($avatar->id);
});

it('clears the avatar when explicitly set to null', function () {
    $owner = anOwner();
    $username = EloquentUser::query()->find($owner->value())->username;
    $avatar = anApprovedAvatar($owner->value());
    app(UpdateProfile::class)($username, 'Bio inicial', $avatar->id, true);

    $profile = app(UpdateProfile::class)($username, 'Bio atualizada', null, true);

    expect($profile->avatarMediaId)->toBeNull();
});
