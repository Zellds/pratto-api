<?php

namespace App\Http\Controllers;

use App\Application\Recipe\DTOs\CreateRecipeInput;
use App\Application\Recipe\DTOs\RecipeIngredientInput;
use App\Application\Recipe\DTOs\RecipeStepInput;
use App\Application\Recipe\DTOs\UpdateRecipeInput;
use App\Application\Recipe\UseCases\ApproveRecipe;
use App\Application\Recipe\UseCases\CreateRecipe;
use App\Application\Recipe\UseCases\DeleteRecipe;
use App\Application\Recipe\UseCases\GetRecipe;
use App\Application\Recipe\UseCases\PublishRecipe;
use App\Application\Recipe\UseCases\RejectRecipe;
use App\Application\Recipe\UseCases\SearchRecipes;
use App\Application\Recipe\UseCases\UpdateRecipe;
use App\Domain\Recipe\Exceptions\CoverMediaNotOwnedException;
use App\Domain\Recipe\Exceptions\InvalidRecipeStatusTransitionException;
use App\Domain\Recipe\Exceptions\RecipeNotFoundException;
use App\Domain\Recipe\Exceptions\RecipeNotOwnedException;
use App\Http\Requests\RejectRecipeRequest;
use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Http\Resources\RecipeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RecipeController extends Controller
{
    public function store(StoreRecipeRequest $request, CreateRecipe $createRecipe): JsonResponse
    {
        try {
            $output = $createRecipe(new CreateRecipeInput(
                $request->user()->id,
                $request->string('title')->value(),
                $request->string('description')->value(),
                (int) $request->integer('portions'),
                (int) $request->integer('prep_time_minutes'),
                $this->ingredientInputs($request),
                $this->stepInputs($request),
                $request->input('cover_media_id'),
            ));
        } catch (CoverMediaNotOwnedException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new RecipeResource($output))->response()->setStatusCode(201);
    }

    public function update(UpdateRecipeRequest $request, string $recipe, UpdateRecipe $updateRecipe): RecipeResource
    {
        try {
            $output = $updateRecipe(new UpdateRecipeInput(
                $recipe,
                $request->user()->id,
                $request->string('title')->value(),
                $request->string('description')->value(),
                (int) $request->integer('portions'),
                (int) $request->integer('prep_time_minutes'),
                $this->ingredientInputs($request),
                $this->stepInputs($request),
                $request->input('cover_media_id'),
                $request->has('cover_media_id'),
            ));
        } catch (RecipeNotFoundException) {
            abort(404);
        } catch (RecipeNotOwnedException) {
            abort(403);
        } catch (CoverMediaNotOwnedException $exception) {
            abort(422, $exception->getMessage());
        }

        return new RecipeResource($output);
    }

    public function publish(Request $request, string $recipe, PublishRecipe $publishRecipe): RecipeResource
    {
        try {
            $output = $publishRecipe($recipe, $request->user()->id);
        } catch (RecipeNotFoundException) {
            abort(404);
        } catch (RecipeNotOwnedException) {
            abort(403);
        } catch (InvalidRecipeStatusTransitionException $exception) {
            abort(409, $exception->getMessage());
        }

        return new RecipeResource($output);
    }

    public function approve(Request $request, string $recipe, ApproveRecipe $approveRecipe): RecipeResource
    {
        try {
            $output = $approveRecipe($recipe, $request->user()->id);
        } catch (RecipeNotFoundException) {
            abort(404);
        }

        return new RecipeResource($output);
    }

    public function reject(RejectRecipeRequest $request, string $recipe, RejectRecipe $rejectRecipe): RecipeResource
    {
        try {
            $output = $rejectRecipe($recipe, $request->user()->id, $request->string('reason')->value());
        } catch (RecipeNotFoundException) {
            abort(404);
        }

        return new RecipeResource($output);
    }

    public function destroy(Request $request, string $recipe, DeleteRecipe $deleteRecipe): Response
    {
        try {
            $deleteRecipe($recipe, $request->user()->id);
        } catch (RecipeNotFoundException) {
            abort(404);
        } catch (RecipeNotOwnedException) {
            abort(403);
        }

        return response()->noContent();
    }

    public function show(Request $request, string $recipe, GetRecipe $getRecipe): RecipeResource
    {
        $portions = $request->validate(['portions' => ['nullable', 'integer', 'min:1']])['portions'] ?? null;
        $viewer = $request->user('sanctum');

        try {
            $output = $getRecipe($recipe, $viewer?->id, $portions);
        } catch (RecipeNotFoundException) {
            abort(404);
        }

        return new RecipeResource($output);
    }

    public function index(Request $request, SearchRecipes $searchRecipes): AnonymousResourceCollection
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'mine' => ['nullable', 'in:0,1,true,false'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $mineOwnerId = null;

        if ($request->boolean('mine')) {
            $viewer = $request->user('sanctum');
            abort_if($viewer === null, 401);
            $mineOwnerId = $viewer->id;
        }

        return RecipeResource::collection(
            $searchRecipes($data['q'] ?? null, $mineOwnerId, $data['page'] ?? 1, 20),
        );
    }

    /**
     * @return list<RecipeIngredientInput>
     */
    private function ingredientInputs(Request $request): array
    {
        return array_map(
            static fn (array $line) => new RecipeIngredientInput(
                $line['ingredient_id'] ?? null,
                $line['ingredient_name'] ?? null,
                (float) $line['quantity'],
                $line['unit'],
                (int) $line['position'],
                (bool) ($line['is_optional'] ?? false),
            ),
            $request->array('ingredients'),
        );
    }

    /**
     * @return list<RecipeStepInput>
     */
    private function stepInputs(Request $request): array
    {
        return array_map(
            static fn (array $step) => new RecipeStepInput((int) $step['position'], $step['instruction']),
            $request->array('steps'),
        );
    }
}
