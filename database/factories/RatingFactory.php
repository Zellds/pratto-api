<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\EloquentRating;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentRecipe;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EloquentRating>
 */
class RatingFactory extends Factory
{
    protected $model = EloquentRating::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'recipe_id' => EloquentRecipe::factory(),
            'user_id' => EloquentUser::factory(),
            'score' => $this->faker->randomElement([3.0, 3.5, 4.0, 4.5, 5.0]),
        ];
    }
}
