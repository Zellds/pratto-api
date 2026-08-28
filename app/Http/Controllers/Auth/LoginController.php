<?php

namespace App\Http\Controllers\Auth;

use App\Application\User\DTOs\LoginUserInput;
use App\Application\User\UseCases\LoginUser;
use App\Domain\User\Exceptions\InvalidCredentialsException;
use App\Domain\User\Exceptions\UserBannedException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(Request $request, LoginUser $loginUser): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        try {
            $output = $loginUser(new LoginUserInput($data['username'], $data['password']));
        } catch (InvalidCredentialsException) {
            throw ValidationException::withMessages(['username' => 'Invalid credentials.']);
        } catch (UserBannedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['token' => $output->token]);
    }
}
