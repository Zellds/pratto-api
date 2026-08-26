<?php

namespace App\Http\Controllers;

use App\Application\Pantry\UseCases\InvitePantryMember;
use App\Application\Pantry\UseCases\LeavePantryMembership;
use App\Application\Pantry\UseCases\ListPantryMembers;
use App\Application\Pantry\UseCases\RemovePantryMember;
use App\Domain\Pantry\Exceptions\CannotInviteSelfException;
use App\Domain\Pantry\Exceptions\PantryLimitExceededException;
use App\Domain\Pantry\Exceptions\PantryMemberLimitExceededException;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Pantry\Exceptions\PantryNotOwnedException;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Http\Requests\InvitePantryMemberRequest;
use App\Http\Resources\PantryMemberResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PantryMemberController extends Controller
{
    public function index(Request $request, string $pantry, ListPantryMembers $listPantryMembers): AnonymousResourceCollection
    {
        try {
            $members = $listPantryMembers($pantry, $request->user()->id);
        } catch (PantryNotFoundException) {
            abort(404);
        }

        return PantryMemberResource::collection($members);
    }

    public function store(InvitePantryMemberRequest $request, string $pantry, InvitePantryMember $invitePantryMember): Response
    {
        try {
            $invitePantryMember($pantry, $request->user()->id, $request->string('username')->value());
        } catch (PantryNotFoundException|UserNotFoundException) {
            abort(404);
        } catch (PantryNotOwnedException) {
            abort(403);
        } catch (CannotInviteSelfException|PantryMemberLimitExceededException|PantryLimitExceededException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->noContent();
    }

    public function destroy(
        Request $request,
        string $pantry,
        string $username,
        LeavePantryMembership $leavePantryMembership,
        RemovePantryMember $removePantryMember,
    ): Response {
        try {
            if ($username === $request->user()->username) {
                $leavePantryMembership($pantry, $request->user()->id);
            } else {
                $removePantryMember($pantry, $request->user()->id, $username);
            }
        } catch (PantryNotFoundException|UserNotFoundException) {
            abort(404);
        } catch (PantryNotOwnedException) {
            abort(403);
        }

        return response()->noContent();
    }
}
