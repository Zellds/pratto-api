<?php

namespace App\Http\Controllers;

use App\Application\Pantry\UseCases\CreatePantry;
use App\Application\Pantry\UseCases\DeletePantry;
use App\Application\Pantry\UseCases\ListMyPantries;
use App\Domain\Pantry\Exceptions\PantryLimitExceededException;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Pantry\Exceptions\PantryNotOwnedException;
use App\Http\Requests\CreatePantryRequest;
use App\Http\Resources\PantryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PantryController extends Controller
{
    public function store(CreatePantryRequest $request, CreatePantry $createPantry): JsonResponse
    {
        try {
            $output = $createPantry($request->user()->id, $request->string('name')->value());
        } catch (PantryLimitExceededException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new PantryResource($output))->response()->setStatusCode(201);
    }

    public function index(Request $request, ListMyPantries $listMyPantries): AnonymousResourceCollection
    {
        return PantryResource::collection($listMyPantries($request->user()->id));
    }

    public function destroy(Request $request, string $pantry, DeletePantry $deletePantry): Response
    {
        try {
            $deletePantry($pantry, $request->user()->id);
        } catch (PantryNotFoundException) {
            abort(404);
        } catch (PantryNotOwnedException) {
            abort(403);
        }

        return response()->noContent();
    }
}
