<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EloquentRecipeStep extends Model
{
    use HasUlids;

    protected $table = 'recipe_steps';

    protected $fillable = ['id', 'recipe_id', 'position', 'instruction'];
}
