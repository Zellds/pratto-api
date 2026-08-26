<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PantryController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/register', RegisterController::class);
Route::post('/login', LoginController::class);
Route::get('/ingredients', [IngredientController::class, 'index']);
Route::get('/recipes', [RecipeController::class, 'index']);
Route::get('/recipes/{recipe}', [RecipeController::class, 'show']);
Route::get('/recipes/{recipe}/comments', [CommentController::class, 'index']);
Route::get('/users/{username}', [UserProfileController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', LogoutController::class);
    Route::get('/me', [UserProfileController::class, 'me']);
    Route::patch('/me', [UserProfileController::class, 'update']);
    Route::post('/recipes', [RecipeController::class, 'store']);
    Route::patch('/recipes/{recipe}', [RecipeController::class, 'update']);
    Route::post('/recipes/{recipe}/publish', [RecipeController::class, 'publish']);
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy']);
    Route::put('/recipes/{recipe}/rating', [RatingController::class, 'store']);
    Route::post('/recipes/{recipe}/comments', [CommentController::class, 'store']);
    Route::patch('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    Route::post('/media', [MediaController::class, 'store']);
    Route::patch('/media/{media}/focal-point', [MediaController::class, 'focalPoint']);

    Route::post('/users/{username}/follow', [FollowController::class, 'store']);
    Route::delete('/users/{username}/follow', [FollowController::class, 'destroy']);
    Route::get('/feed', [FeedController::class, 'index']);

    Route::post('/pantries', [PantryController::class, 'store']);
    Route::get('/pantries', [PantryController::class, 'index']);
    Route::delete('/pantries/{pantry}', [PantryController::class, 'destroy']);

    Route::middleware('admin')->group(function () {
        Route::patch('/media/{media}/approve', [MediaController::class, 'approve']);
        Route::patch('/media/{media}/reject', [MediaController::class, 'reject']);
    });
});
