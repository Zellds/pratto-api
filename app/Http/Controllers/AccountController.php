<?php

namespace App\Http\Controllers;

use App\Application\User\UseCases\LinkGoogleAccount;
use App\Application\User\UseCases\SetPassword;
use App\Domain\User\Exceptions\GoogleAccountAlreadyLinkedException;
use App\Domain\User\Exceptions\InvalidGoogleTokenException;
use App\Http\Requests\LinkGoogleAccountRequest;
use App\Http\Requests\SetPasswordRequest;
use Illuminate\Http\Response;

class AccountController extends Controller
{
    public function linkGoogle(LinkGoogleAccountRequest $request, LinkGoogleAccount $linkGoogleAccount): Response
    {
        try {
            $linkGoogleAccount($request->user()->id, $request->string('id_token')->value());
        } catch (InvalidGoogleTokenException $exception) {
            abort(422, $exception->getMessage());
        } catch (GoogleAccountAlreadyLinkedException $exception) {
            abort(409, $exception->getMessage());
        }

        return response()->noContent();
    }

    public function setPassword(SetPasswordRequest $request, SetPassword $setPassword): Response
    {
        $setPassword($request->user()->id, $request->string('password')->value());

        return response()->noContent();
    }
}
