<?php

namespace App\Http\Controllers;

use App\Application\User\UseCases\GetUserProfile;
use App\Application\User\UseCases\UpdateProfile;
use App\Domain\User\Exceptions\AvatarMediaNotOwnedException;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserProfileResource;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(Request $request, GetUserProfile $getUserProfile): UserProfileResource
    {
        $profile = $getUserProfile($request->user()->username);

        abort_if($profile === null, 404);

        return new UserProfileResource($profile);
    }

    public function update(UpdateProfileRequest $request, UpdateProfile $updateProfile): UserProfileResource
    {
        try {
            $profile = $updateProfile(
                $request->user()->username,
                $request->string('bio')->value(),
                $request->input('avatar_media_id'),
                $request->has('avatar_media_id'),
            );
        } catch (AvatarMediaNotOwnedException $exception) {
            abort(422, $exception->getMessage());
        }

        return new UserProfileResource($profile);
    }
}
