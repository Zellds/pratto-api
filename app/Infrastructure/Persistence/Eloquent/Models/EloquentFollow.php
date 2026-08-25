<?php
// app/Infrastructure/Persistence/Eloquent/Models/EloquentFollow.php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EloquentFollow extends Model
{
    use HasUlids;

    protected $table = 'follows';

    public $timestamps = false;

    protected $fillable = ['id', 'follower_id', 'followee_id', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
