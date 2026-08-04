<?php

namespace App\Infrastructure\Auth;

use App\Domain\Shared\Ulid;
use App\Domain\User\AccessTokenIssuerInterface;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;

final class SanctumAccessTokenIssuer implements AccessTokenIssuerInterface
{
    public function issueFor(Ulid $id): string
    {
        $record = EloquentUser::query()->findOrFail($id->value());

        return $record->createToken('api')->plainTextToken;
    }
}
