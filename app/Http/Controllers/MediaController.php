<?php

namespace App\Http\Controllers;

use App\Application\Media\DTOs\UploadMediaInput;
use App\Application\Media\UseCases\AdjustFocalPoint;
use App\Application\Media\UseCases\ApproveMedia;
use App\Application\Media\UseCases\RejectMedia;
use App\Application\Media\UseCases\UploadMedia;
use App\Domain\Media\Exceptions\InvalidImageException;
use App\Domain\Media\Exceptions\MediaNotFoundException;
use App\Domain\Media\Exceptions\MediaNotOwnedException;
use App\Http\Requests\FocalPointRequest;
use App\Http\Requests\RejectMediaRequest;
use App\Http\Requests\UploadMediaRequest;
use App\Http\Resources\MediaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function store(UploadMediaRequest $request, UploadMedia $uploadMedia): JsonResponse
    {
        try {
            $output = $uploadMedia(new UploadMediaInput(
                $request->user()->id,
                $request->string('kind')->value(),
                $request->file('file')->getRealPath(),
                $request->has('focal_x') ? (float) $request->input('focal_x') : null,
                $request->has('focal_y') ? (float) $request->input('focal_y') : null,
            ));
        } catch (InvalidImageException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new MediaResource($output))->response()->setStatusCode(201);
    }

    public function focalPoint(FocalPointRequest $request, string $media, AdjustFocalPoint $adjustFocalPoint): MediaResource
    {
        try {
            $output = $adjustFocalPoint(
                $media,
                $request->user()->id,
                (float) $request->input('focal_x'),
                (float) $request->input('focal_y'),
            );
        } catch (MediaNotFoundException) {
            abort(404);
        } catch (MediaNotOwnedException) {
            abort(403);
        }

        return new MediaResource($output);
    }

    public function approve(Request $request, string $media, ApproveMedia $approveMedia): MediaResource
    {
        try {
            $output = $approveMedia($media, $request->user()->id);
        } catch (MediaNotFoundException) {
            abort(404);
        }

        return new MediaResource($output);
    }

    public function reject(RejectMediaRequest $request, string $media, RejectMedia $rejectMedia): MediaResource
    {
        try {
            $reason = $request->string('reason')->value();
            $output = $rejectMedia($media, $request->user()->id, $reason === '' ? null : $reason);
        } catch (MediaNotFoundException) {
            abort(404);
        }

        return new MediaResource($output);
    }
}
