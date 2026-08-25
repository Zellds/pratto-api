<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EloquentRating extends Model
{
    use HasUlids;

    protected $table = 'ratings';

    protected $fillable = ['id', 'recipe_id', 'user_id', 'score'];
}
