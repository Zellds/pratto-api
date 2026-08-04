<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        // Sanctum's request guard caches the resolved user for the lifetime of the
        // application instance. Without forgetting it here, a subsequent request
        // reusing the same container (as happens in feature tests) would still
        // see the just-revoked token as authenticated.
        Auth::forgetGuards();

        return response()->noContent();
    }
}
