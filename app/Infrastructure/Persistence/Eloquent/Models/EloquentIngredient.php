<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EloquentIngredient extends Model
{
    use HasFactory;
    use HasUlids;

    protected $table = 'ingredients';

    protected $fillable = ['id', 'name', 'normalized_name', 'status'];

    /**
     * The factory class name doesn't match the default `Eloquent{Model}Factory`
     * convention Laravel would derive from this class's namespace, so it must
     * be resolved explicitly.
     */
    protected static function newFactory(): Factory
    {
        return IngredientFactory::new();
    }
}
