<?php

namespace App\Infrastructure\Providers;

use App\Domain\Ingredient\IngredientRepositoryInterface;
use App\Domain\Recipe\RecipeRepositoryInterface;
use App\Domain\User\AccessTokenIssuerInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Auth\SanctumAccessTokenIssuer;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentIngredientRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentRecipeRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

final class DomainServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(AccessTokenIssuerInterface::class, SanctumAccessTokenIssuer::class);
        $this->app->bind(IngredientRepositoryInterface::class, EloquentIngredientRepository::class);
        $this->app->bind(RecipeRepositoryInterface::class, EloquentRecipeRepository::class);
    }
}
