<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EloquentRecipeIngredient extends Model
{
    use HasUlids;

    protected $table = 'recipe_ingredients';

    protected $fillable = ['id', 'recipe_id', 'ingredient_id', 'quantity', 'unit', 'position'];
}
