<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\EloquentIngredient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EloquentIngredient>
 */
class IngredientFactory extends Factory
{
    protected $model = EloquentIngredient::class;

    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->word());

        return [
            'id' => (string) Str::ulid(),
            'name' => $name,
            'normalized_name' => Str::slug($name, '_'),
            'status' => 'approved',
        ];
    }
}
