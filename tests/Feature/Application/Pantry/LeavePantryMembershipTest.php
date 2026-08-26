<?php

// tests/Feature/Application/Pantry/LeavePantryMembershipTest.php

use App\Application\Pantry\UseCases\LeavePantryMembership;
use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Shared\Ulid;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets a member leave', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $member = anOwner();
    aPantryMember($pantry->id, $owner->value(), EloquentUser::query()->whereKey($member->value())->value('username'));

    app(LeavePantryMembership::class)($pantry->id, $member->value());

    expect(app(PantryMembershipRepositoryInterface::class)->isMember(Ulid::fromString($pantry->id), $member))->toBeFalse();
});

it('is idempotent when leaving a pantry the user was never a member of', function () {
    $pantry = aPantry(anOwner()->value());

    app(LeavePantryMembership::class)($pantry->id, anOwner()->value());

    expect(true)->toBeTrue();
});

it('throws when the pantry does not exist', function () {
    app(LeavePantryMembership::class)((string) new Symfony\Component\Uid\Ulid, anOwner()->value());
})->throws(PantryNotFoundException::class);
