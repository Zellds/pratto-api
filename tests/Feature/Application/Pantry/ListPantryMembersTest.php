<?php

// tests/Feature/Application/Pantry/ListPantryMembersTest.php

use App\Application\Pantry\UseCases\ListPantryMembers;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists the owner and every member', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $member = anOwner();
    aPantryMember($pantry->id, $owner->value(), EloquentUser::query()->whereKey($member->value())->value('username'));

    $results = app(ListPantryMembers::class)($pantry->id, $owner->value());

    $byRole = collect($results)->groupBy('role');

    expect($byRole['owner'])->toHaveCount(1)
        ->and($byRole['owner'][0]->userId)->toBe($owner->value())
        ->and($byRole['member'])->toHaveCount(1)
        ->and($byRole['member'][0]->userId)->toBe($member->value());
});

it('throws when the actor has no access', function () {
    $pantry = aPantry(anOwner()->value());

    app(ListPantryMembers::class)($pantry->id, anOwner()->value());
})->throws(PantryNotFoundException::class);
