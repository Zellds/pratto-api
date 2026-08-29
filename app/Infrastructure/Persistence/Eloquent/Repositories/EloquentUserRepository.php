<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\DisplayName;
use App\Domain\User\Enums\UserRole;
use App\Domain\User\Enums\UserStatus;
use App\Domain\User\Exceptions\DuplicateUsernameException;
use App\Domain\User\Exceptions\GoogleAccountAlreadyLinkedException;
use App\Domain\User\User;
use App\Domain\User\Username;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use DateTimeImmutable;
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
                'avatar_media_id' => $user->avatarMediaId()?->value(),
                'password' => $password,
                'role' => $user->role()->value,
                'status' => $user->status()->value,
                'banned_at' => $user->bannedAt(),
                'ban_reason' => $user->banReason(),
                'banned_by' => $user->bannedBy()?->value(),
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

    public function findById(Ulid $id): ?User
    {
        $record = EloquentUser::query()->find($id->value());

        return $record === null ? null : $this->toDomain($record);
    }

    public function findByGoogleId(string $googleId): ?User
    {
        $record = EloquentUser::query()->where('google_id', $googleId)->first();

        return $record === null ? null : $this->toDomain($record);
    }

    public function registerWithGoogle(User $user, string $googleId, ?string $email): void
    {
        try {
            DB::transaction(function () use ($user, $googleId, $email): void {
                EloquentUser::query()->create([
                    'id' => $user->id()->value(),
                    'username' => $user->username()->value(),
                    'display_name' => $user->displayName()->value(),
                    'bio' => $user->bio(),
                    'password' => bcrypt(str()->random(32)),
                    'google_id' => $googleId,
                    'email' => $email,
                ]);
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                if (str_contains(strtolower($this->driverErrorMessage($exception)), 'google_id')) {
                    throw GoogleAccountAlreadyLinkedException::forGoogleId($googleId);
                }

                throw DuplicateUsernameException::forUsername($user->username());
            }

            throw $exception;
        }
    }

    /**
     * Isolates the database driver's own error text from the exception
     * message, stripping the "(Connection: ..., SQL: ...)" debug suffix
     * Laravel appends. Without this, the appended SQL — which always
     * lists every inserted column, including google_id — would make a
     * plain username collision look like a google_id collision too.
     */
    private function driverErrorMessage(QueryException $exception): string
    {
        $driverMessage = strstr($exception->getMessage(), ' (Connection: ', true);

        return $driverMessage !== false ? $driverMessage : $exception->getMessage();
    }

    public function linkGoogleId(Ulid $id, string $googleId, ?string $email): void
    {
        try {
            EloquentUser::query()->whereKey($id->value())->update(['google_id' => $googleId, 'email' => $email]);
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                throw GoogleAccountAlreadyLinkedException::forGoogleId($googleId);
            }

            throw $exception;
        }
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

        if ($record->avatar_media_id !== null) {
            $user->updateAvatar(Ulid::fromString($record->avatar_media_id));
        }

        $user->restoreModerationState(
            UserRole::from($record->role),
            UserStatus::from($record->status),
            $record->banned_at !== null ? DateTimeImmutable::createFromInterface($record->banned_at) : null,
            $record->ban_reason,
            $record->banned_by !== null ? Ulid::fromString($record->banned_by) : null,
        );

        return $user;
    }
}
