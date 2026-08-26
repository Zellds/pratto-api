<?php

// app/Infrastructure/Persistence/Eloquent/Models/EloquentPantry.php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EloquentPantry extends Model
{
    use HasUlids;

    protected $table = 'pantries';

    protected $fillable = ['id', 'owner_id', 'name'];
}
