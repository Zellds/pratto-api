<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Shared\Ulid;
use App\Domain\User\DisplayName;
use App\Domain\User\User;
use App\Domain\User\Username;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function findByUsername(Username $username): ?User
    {
        $record = EloquentUser::query()->where('username', $username->value())->first();

        if ($record === null) {
            return null;
        }

        return $this->toDomain($record);
    }

    public function save(User $user): void
    {
        $existing = EloquentUser::query()->find($user->id()->value());
        $password = $existing !== null ? $existing->password : bcrypt(str()->random(32));

        EloquentUser::query()->updateOrCreate(
            ['id' => $user->id()->value()],
            [
                'username' => $user->username()->value(),
                'display_name' => $user->displayName()->value(),
                'bio' => $user->bio(),
                'password' => $password,
            ],
        );
    }

    private function toDomain(EloquentUser $record): User
    {
        $user = User::register(
            Ulid::fromString($record->id),
            Username::fromString($record->username),
            DisplayName::fromString($record->display_name),
        );

        if ($record->bio !== null) {
            $user->updateBio($record->bio);
        }

        return $user;
    }
}
