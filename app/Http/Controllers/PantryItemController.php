<?php

// app/Http/Controllers/PantryItemController.php

namespace App\Http\Controllers;

use App\Application\Pantry\UseCases\AddPantryItem;
use App\Application\Pantry\UseCases\DeletePantryItem;
use App\Application\Pantry\UseCases\ListPantryItems;
use App\Application\Pantry\UseCases\UpdatePantryItem;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Recipe\Enums\MeasurementUnit;
use App\Http\Requests\AddPantryItemRequest;
use App\Http\Requests\UpdatePantryItemRequest;
use App\Http\Resources\PantryItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PantryItemController extends Controller
{
    public function index(Request $request, string $pantry, ListPantryItems $listPantryItems): AnonymousResourceCollection
    {
        try {
            $items = $listPantryItems($pantry, $request->user()->id);
        } catch (PantryNotFoundException) {
            abort(404);
        }

        return PantryItemResource::collection($items);
    }

    public function store(AddPantryItemRequest $request, string $pantry, AddPantryItem $addPantryItem): JsonResponse
    {
        try {
            $output = $addPantryItem(
                $pantry,
                $request->user()->id,
                $request->input('ingredient_id'),
                $request->input('ingredient_name'),
                $request->has('quantity') ? (float) $request->input('quantity') : 1.0,
                MeasurementUnit::from($request->input('unit', 'unidade')),
                $request->boolean('is_fixed'),
            );
        } catch (PantryNotFoundException) {
            abort(404);
        }

        return (new PantryItemResource($output))->response()->setStatusCode(201);
    }

    public function update(UpdatePantryItemRequest $request, string $pantry, string $item, UpdatePantryItem $updatePantryItem): PantryItemResource
    {
        try {
            $output = $updatePantryItem(
                $item,
                $request->user()->id,
                $request->has('needs_to_buy') ? $request->boolean('needs_to_buy') : null,
                $request->has('quantity') ? (float) $request->input('quantity') : null,
                $request->has('unit') ? MeasurementUnit::from($request->input('unit')) : null,
                $request->has('is_fixed') ? $request->boolean('is_fixed') : null,
            );
        } catch (PantryNotFoundException) {
            abort(404);
        }

        return new PantryItemResource($output);
    }

    public function destroy(Request $request, string $pantry, string $item, DeletePantryItem $deletePantryItem): Response
    {
        try {
            $deletePantryItem($item, $request->user()->id);
        } catch (PantryNotFoundException) {
            abort(404);
        }

        return response()->noContent();
    }
}
