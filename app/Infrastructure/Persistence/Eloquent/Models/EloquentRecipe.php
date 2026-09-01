<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentRecipe extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'recipes';

    protected $fillable = [
        'id', 'user_id', 'title', 'description', 'portions', 'prep_time_minutes', 'status', 'cover_media_id',
        'rejection_reason', 'was_ever_rejected', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'was_ever_rejected' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    /**
     * @return HasMany<EloquentRecipeIngredient, $this>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(EloquentRecipeIngredient::class, 'recipe_id')->orderBy('position');
    }

    /**
     * @return HasMany<EloquentRecipeStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(EloquentRecipeStep::class, 'recipe_id')->orderBy('position');
    }

    /**
     * The factory class name doesn't match the default `Eloquent{Model}Factory`
     * convention Laravel would derive from this class's namespace, so it must
     * be resolved explicitly.
     */
    protected static function newFactory(): Factory
    {
        return RecipeFactory::new();
    }
}
