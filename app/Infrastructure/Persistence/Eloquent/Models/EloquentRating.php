<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\RatingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EloquentRating extends Model
{
    use HasFactory;
    use HasUlids;

    protected $table = 'ratings';

    protected $fillable = ['id', 'recipe_id', 'user_id', 'score'];

    /**
     * The factory class name doesn't match the default `Eloquent{Model}Factory`
     * convention Laravel would derive from this class's namespace, so it must
     * be resolved explicitly.
     */
    protected static function newFactory(): Factory
    {
        return RatingFactory::new();
    }
}
