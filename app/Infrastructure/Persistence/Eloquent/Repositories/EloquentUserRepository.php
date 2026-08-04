<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Shared\Ulid;
use App\Domain\User\DisplayName;
use App\Domain\User\DuplicateUsernameException;
use App\Domain\User\User;
use App\Domain\User\Username;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

    public function registerWithPassword(User $user, string $plainPassword): void
    {
        try {
            DB::transaction(function () use ($user, $plainPassword): void {
                EloquentUser::query()->create([
                    'id' => $user->id()->value(),
                    'username' => $user->username()->value(),
                    'display_name' => $user->displayName()->value(),
                    'bio' => $user->bio(),
                    'password' => Hash::make($plainPassword),
                ]);
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                throw DuplicateUsernameException::forUsername($user->username());
            }

            throw $exception;
        }
    }

    public function setPassword(Ulid $id, string $plainPassword): void
    {
        EloquentUser::query()->whereKey($id->value())->update([
            'password' => Hash::make($plainPassword),
        ]);
    }

    /**
     * Detects a unique-constraint violation across common drivers (Postgres
     * SQLSTATE 23505, MySQL error 1062) without depending on a specific one.
     */
    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return $exception->getCode() === '23505'
            || str_contains($exception->getMessage(), 'Integrity constraint violation')
            || str_contains(strtolower($exception->getMessage()), 'unique constraint');
    }

    public function verifyCredentials(Username $username, string $plainPassword): ?User
    {
        $record = EloquentUser::query()->where('username', $username->value())->first();

        if ($record === null || ! Hash::check($plainPassword, $record->password)) {
            return null;
        }

        return $this->toDomain($record);
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
