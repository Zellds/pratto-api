<?php

namespace App\Http\Controllers\Auth;

use App\Domain\User\Username;
use App\Domain\User\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class LoginController extends Controller
{
    public function __invoke(Request $request, UserRepositoryInterface $users): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        try {
            $username = Username::fromString($data['username']);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['username' => 'Invalid credentials.']);
        }

        $user = $users->verifyCredentials($username, $data['password']);

        if ($user === null) {
            throw ValidationException::withMessages(['username' => 'Invalid credentials.']);
        }

        return response()->json(['token' => $users->issueToken($user->id())]);
    }
}
