<?php

namespace App\Http\Controllers\Auth;

use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\RegisterUser;
use App\Domain\Shared\Ulid;
use App\Domain\User\DuplicateUsernameException;
use App\Domain\User\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\UserProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function __invoke(
        RegisterUserRequest $request,
        RegisterUser $registerUser,
        UserRepositoryInterface $users,
    ): JsonResponse {
        try {
            $profile = $registerUser(new RegisterUserInput(
                $request->string('username')->value(),
                $request->string('display_name')->value(),
                $request->string('password')->value(),
            ));
        } catch (DuplicateUsernameException $exception) {
            throw ValidationException::withMessages(['username' => $exception->getMessage()]);
        }

        $userId = Ulid::fromString($profile->id);
        $users->setPassword($userId, $request->string('password')->value());
        $token = $users->issueToken($userId);

        return response()->json([
            'token' => $token,
            'user' => (new UserProfileResource($profile))->resolve($request),
        ], 201);
    }
}
