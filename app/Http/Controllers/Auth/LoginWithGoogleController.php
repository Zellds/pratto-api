<?php

namespace App\Http\Controllers\Auth;

use App\Application\User\UseCases\LoginWithGoogle;
use App\Domain\User\Exceptions\InvalidGoogleTokenException;
use App\Domain\User\Exceptions\UserBannedException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginWithGoogleController extends Controller
{
    public function __invoke(Request $request, LoginWithGoogle $loginWithGoogle): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        try {
            $output = $loginWithGoogle($data['id_token']);
        } catch (InvalidGoogleTokenException $exception) {
            abort(422, $exception->getMessage());
        } catch (UserBannedException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        return response()->json(['token' => $output->token]);
    }
}
