<?php

namespace App\Http\Controllers;

use App\Application\User\UseCases\BanUser;
use App\Application\User\UseCases\PromoteToAdmin;
use App\Application\User\UseCases\UnbanUser;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Http\Requests\BanUserRequest;
use App\Http\Resources\UserProfileResource;

class UserModerationController extends Controller
{
    public function promote(string $username, PromoteToAdmin $promoteToAdmin): UserProfileResource
    {
        try {
            $output = $promoteToAdmin($username);
        } catch (UserNotFoundException) {
            abort(404);
        }

        return new UserProfileResource($output);
    }

    public function ban(BanUserRequest $request, string $username, BanUser $banUser): UserProfileResource
    {
        try {
            $output = $banUser($username, $request->user()->id, $request->string('reason')->value());
        } catch (UserNotFoundException) {
            abort(404);
        }

        return new UserProfileResource($output);
    }

    public function unban(string $username, UnbanUser $unbanUser): UserProfileResource
    {
        try {
            $output = $unbanUser($username);
        } catch (UserNotFoundException) {
            abort(404);
        }

        return new UserProfileResource($output);
    }
}
