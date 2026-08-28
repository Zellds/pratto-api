<?php

namespace App\Http\Controllers;

use App\Application\Comment\UseCases\AdminDeleteComment;
use App\Application\Comment\UseCases\DeleteComment;
use App\Application\Comment\UseCases\EditComment;
use App\Application\Comment\UseCases\ListComments;
use App\Application\Comment\UseCases\PostComment;
use App\Domain\Comment\Exceptions\CommentNotFoundException;
use App\Domain\Comment\Exceptions\CommentNotOwnedException;
use App\Domain\Recipe\Exceptions\RecipeNotFoundException;
use App\Http\Requests\PostCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    public function store(PostCommentRequest $request, string $recipe, PostComment $postComment): JsonResponse
    {
        try {
            $output = $postComment($recipe, $request->user()->id, $request->string('body')->value());
        } catch (RecipeNotFoundException) {
            abort(404);
        }

        return (new CommentResource($output))->response()->setStatusCode(201);
    }

    public function update(UpdateCommentRequest $request, string $comment, EditComment $editComment): CommentResource
    {
        try {
            $output = $editComment($comment, $request->user()->id, $request->string('body')->value());
        } catch (CommentNotFoundException) {
            abort(404);
        } catch (CommentNotOwnedException) {
            abort(403);
        }

        return new CommentResource($output);
    }

    public function destroy(Request $request, string $comment, DeleteComment $deleteComment, AdminDeleteComment $adminDeleteComment): Response
    {
        try {
            if ($request->user()->role === 'admin') {
                $adminDeleteComment($comment);
            } else {
                $deleteComment($comment, $request->user()->id);
            }
        } catch (CommentNotFoundException) {
            abort(404);
        } catch (CommentNotOwnedException) {
            abort(403);
        }

        return response()->noContent();
    }

    public function index(Request $request, string $recipe, ListComments $listComments): AnonymousResourceCollection
    {
        $data = $request->validate(['page' => ['nullable', 'integer', 'min:1']]);

        try {
            $comments = $listComments($recipe, $data['page'] ?? 1, 20);
        } catch (RecipeNotFoundException) {
            abort(404);
        }

        return CommentResource::collection($comments);
    }
}
