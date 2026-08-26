<?php

// app/Infrastructure/Persistence/Eloquent/Models/EloquentPantryItem.php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EloquentPantryItem extends Model
{
    use HasUlids;

    protected $table = 'pantry_items';

    protected $fillable = ['id', 'pantry_id', 'ingredient_id', 'quantity', 'unit', 'needs_to_buy', 'is_fixed'];

    protected $casts = [
        'needs_to_buy' => 'boolean',
        'is_fixed' => 'boolean',
    ];
}
