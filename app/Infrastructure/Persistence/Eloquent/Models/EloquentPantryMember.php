<?php

// app/Infrastructure/Persistence/Eloquent/Models/EloquentPantryMember.php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EloquentPantryMember extends Model
{
    use HasUlids;

    protected $table = 'pantry_members';

    public $timestamps = false;

    protected $fillable = ['id', 'pantry_id', 'user_id', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
