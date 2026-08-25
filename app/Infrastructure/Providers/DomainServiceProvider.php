<?php

namespace App\Infrastructure\Providers;

use App\Domain\Ingredient\Contracts\IngredientRepositoryInterface;
use App\Domain\Media\Contracts\ImageProcessorInterface;
use App\Domain\Media\Contracts\MediaRepositoryInterface;
use App\Domain\Media\Contracts\MediaUrlSignerInterface;
use App\Domain\Rating\Contracts\RatingRepositoryInterface;
use App\Domain\Recipe\Contracts\RecipeRepositoryInterface;
use App\Domain\User\Contracts\AccessTokenIssuerInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Infrastructure\Auth\SanctumAccessTokenIssuer;
use App\Infrastructure\Media\ImagePipeline;
use App\Infrastructure\Media\TemporaryMediaUrlSigner;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentIngredientRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentMediaRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentRatingRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentRecipeRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageManager;

final class DomainServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(AccessTokenIssuerInterface::class, SanctumAccessTokenIssuer::class);
        $this->app->bind(IngredientRepositoryInterface::class, EloquentIngredientRepository::class);
        $this->app->bind(RecipeRepositoryInterface::class, EloquentRecipeRepository::class);
        $this->app->bind(MediaRepositoryInterface::class, EloquentMediaRepository::class);
        $this->app->bind(RatingRepositoryInterface::class, EloquentRatingRepository::class);
        $this->app->singleton(ImageManager::class, static fn () => ImageManager::gd());
        $this->app->bind(ImageProcessorInterface::class, ImagePipeline::class);
        $this->app->bind(MediaUrlSignerInterface::class, TemporaryMediaUrlSigner::class);
    }
}
