<?php

namespace App\Http\Controllers;

use App\Application\Ingredient\UseCases\ApproveIngredient;
use App\Application\Ingredient\UseCases\RejectIngredient;
use App\Application\Ingredient\UseCases\SearchIngredients;
use App\Domain\Ingredient\Exceptions\IngredientNotFoundException;
use App\Http\Resources\IngredientResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IngredientController extends Controller
{
    public function index(Request $request, SearchIngredients $searchIngredients): AnonymousResourceCollection
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:1']]);

        return IngredientResource::collection($searchIngredients($data['q']));
    }

    public function approve(string $ingredient, ApproveIngredient $approveIngredient): IngredientResource
    {
        try {
            $output = $approveIngredient($ingredient);
        } catch (IngredientNotFoundException) {
            abort(404);
        }

        return new IngredientResource($output);
    }

    public function reject(string $ingredient, RejectIngredient $rejectIngredient): IngredientResource
    {
        try {
            $output = $rejectIngredient($ingredient);
        } catch (IngredientNotFoundException) {
            abort(404);
        }

        return new IngredientResource($output);
    }
}
