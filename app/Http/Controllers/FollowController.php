<?php

namespace App\Http\Controllers;

use App\Application\Follow\UseCases\FollowUser;
use App\Application\Follow\UseCases\UnfollowUser;
use App\Domain\Follow\Exceptions\CannotFollowSelfException;
use App\Domain\User\Exceptions\UserNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FollowController extends Controller
{
    public function store(Request $request, string $username, FollowUser $followUser): Response
    {
        try {
            $followUser($request->user()->id, $username);
        } catch (UserNotFoundException) {
            abort(404);
        } catch (CannotFollowSelfException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->noContent();
    }

    public function destroy(Request $request, string $username, UnfollowUser $unfollowUser): Response
    {
        try {
            $unfollowUser($request->user()->id, $username);
        } catch (UserNotFoundException) {
            abort(404);
        }

        return response()->noContent();
    }
}
