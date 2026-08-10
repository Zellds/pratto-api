<?php

namespace App\Http\Controllers;

use App\Application\Ingredient\UseCases\SearchIngredients;
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
}
