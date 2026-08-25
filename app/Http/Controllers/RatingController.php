<?php

namespace App\Http\Controllers;

use App\Application\Rating\UseCases\RateRecipe;
use App\Domain\Recipe\Exceptions\RecipeNotFoundException;
use App\Http\Requests\RateRecipeRequest;
use App\Http\Resources\RatingResource;

class RatingController extends Controller
{
    public function store(RateRecipeRequest $request, string $recipe, RateRecipe $rateRecipe): RatingResource
    {
        try {
            $output = $rateRecipe($recipe, $request->user()->id, (float) $request->input('score'));
        } catch (RecipeNotFoundException) {
            abort(404);
        }

        return new RatingResource($output);
    }
}
