<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\EloquentFollow;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentRating;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Database\Factories\RecipeFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Popula um cenário de desenvolvimento realista — usado só localmente,
     * nunca em produção. `usuario_teste`/`senha123` é o login fixo pra QA
     * manual do frontend (ver README do frontend).
     */
    public function run(): void
    {
        $testUser = EloquentUser::factory()->create([
            'id' => (string) Str::ulid(),
            'username' => 'usuario_teste',
            'display_name' => 'Usuário de Teste',
            'password' => Hash::make('senha123'),
        ]);

        $otherUsers = EloquentUser::factory()->count(5)->create();

        $allUsers = $otherUsers->concat([$testUser]);

        $recipes = collect();

        foreach ($allUsers as $user) {
            $recipes = $recipes->merge(
                RecipeFactory::new()
                    ->withIngredientsAndSteps()
                    ->count(2)
                    ->create(['user_id' => $user->id]),
            );
        }

        foreach ($recipes as $recipe) {
            $raters = $allUsers->random(min(3, $allUsers->count()));

            foreach ($raters as $rater) {
                EloquentRating::factory()->create([
                    'recipe_id' => $recipe->id,
                    'user_id' => $rater->id,
                ]);
            }
        }

        foreach ($otherUsers->take(2) as $followee) {
            EloquentFollow::factory()->create([
                'follower_id' => $testUser->id,
                'followee_id' => $followee->id,
            ]);
        }
    }
}
