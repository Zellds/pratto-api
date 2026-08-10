<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EloquentIngredient extends Model
{
    use HasUlids;

    protected $table = 'ingredients';

    protected $fillable = ['id', 'name', 'normalized_name', 'status'];
}
