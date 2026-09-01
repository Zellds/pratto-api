<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<EloquentUser>
 */
class UserFactory extends Factory
{
    protected $model = EloquentUser::class;

    public function definition(): array
    {
        $username = Str::slug($this->faker->unique()->userName(), '_');

        return [
            'id' => (string) Str::ulid(),
            'username' => $username,
            'display_name' => $this->faker->name(),
            'password' => Hash::make('senha123'),
            'bio' => $this->faker->optional()->sentence(),
            'role' => 'user',
            'status' => 'active',
        ];
    }
}
