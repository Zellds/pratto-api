<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\EloquentIngredient;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentRecipe;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EloquentRecipe>
 */
class RecipeFactory extends Factory
{
    protected $model = EloquentRecipe::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => EloquentUser::factory(),
            'title' => ucfirst($this->faker->words(3, true)),
            'description' => $this->faker->paragraph(),
            'portions' => $this->faker->numberBetween(2, 8),
            'prep_time_minutes' => $this->faker->numberBetween(15, 120),
            'status' => 'published',
            'was_ever_rejected' => false,
            'cover_media_id' => null,
        ];
    }

    /**
     * Creates the ingredient and step rows a published recipe needs to be
     * a realistic, renderable seed row — the plain factory alone leaves a
     * recipe with no ingredients/steps, which the frontend never sees for
     * a real recipe (StoreRecipeRequest always requires at least one of each).
     */
    public function withIngredientsAndSteps(): static
    {
        return $this->afterCreating(function (EloquentRecipe $recipe) {
            $ingredient = EloquentIngredient::factory()->create();

            $recipe->ingredients()->create([
                'id' => (string) Str::ulid(),
                'ingredient_id' => $ingredient->id,
                'quantity' => $this->faker->randomFloat(2, 0.5, 5),
                'unit' => $this->faker->randomElement(['g', 'kg', 'ml', 'unidade', 'colher_sopa']),
                'position' => 0,
            ]);

            $recipe->steps()->create([
                'id' => (string) Str::ulid(),
                'position' => 0,
                'instruction' => $this->faker->sentence(),
            ]);
        });
    }
}
