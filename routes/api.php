<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/register', RegisterController::class);
Route::post('/login', LoginController::class);
Route::get('/ingredients', [IngredientController::class, 'index']);
Route::get('/recipes', [RecipeController::class, 'index']);
Route::get('/recipes/{recipe}', [RecipeController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', LogoutController::class);
    Route::get('/me', [UserProfileController::class, 'show']);
    Route::patch('/me', [UserProfileController::class, 'update']);
    Route::post('/recipes', [RecipeController::class, 'store']);
    Route::patch('/recipes/{recipe}', [RecipeController::class, 'update']);
    Route::post('/recipes/{recipe}/publish', [RecipeController::class, 'publish']);
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy']);
});
