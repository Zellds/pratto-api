<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EloquentComment extends Model
{
    use HasUlids;

    protected $table = 'comments';

    protected $fillable = ['id', 'recipe_id', 'user_id', 'body', 'edited_at'];

    protected $casts = [
        'edited_at' => 'datetime',
    ];
}
