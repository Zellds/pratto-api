<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\EloquentFollow;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EloquentFollow>
 */
class FollowFactory extends Factory
{
    protected $model = EloquentFollow::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'follower_id' => EloquentUser::factory(),
            'followee_id' => EloquentUser::factory(),
        ];
    }
}
