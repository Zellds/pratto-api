<?php

namespace App\Http\Controllers;

use App\Application\User\UseCases\GetUserProfile;
use App\Application\User\UseCases\UpdateProfile;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserProfileResource;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(Request $request, GetUserProfile $getUserProfile): UserProfileResource
    {
        $profile = $getUserProfile($request->user()->username);

        return new UserProfileResource($profile);
    }

    public function update(UpdateProfileRequest $request, UpdateProfile $updateProfile): UserProfileResource
    {
        $profile = $updateProfile($request->user()->username, $request->string('bio')->value());

        return new UserProfileResource($profile);
    }
}
