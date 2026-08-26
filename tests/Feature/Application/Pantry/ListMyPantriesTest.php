<?php

// tests/Feature/Application/Pantry/ListMyPantriesTest.php

use App\Application\Pantry\UseCases\ListMyPantries;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists pantries the user owns and pantries they are a member of, tagged with role', function () {
    $user = anOwner();
    $own = aPantry($user->value(), 'Própria');
    $othersPantry = aPantry(anOwner()->value(), 'De outro dono');
    $userUsername = EloquentUser::query()->whereKey($user->value())->value('username');
    aPantryMember($othersPantry->id, $othersPantry->ownerId, $userUsername);

    $results = app(ListMyPantries::class)($user->value());

    $byId = collect($results)->keyBy('id');

    expect($byId[$own->id]->role)->toBe('owner')
        ->and($byId[$othersPantry->id]->role)->toBe('member');
});

it('returns an empty list for a user with no pantries', function () {
    $results = app(ListMyPantries::class)(anOwner()->value());

    expect($results)->toBe([]);
});
