<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class EloquentUser extends Authenticatable
{
    use HasApiTokens;
    use HasUlids;

    protected $table = 'users';

    protected $fillable = [
        'id',
        'username',
        'display_name',
        'bio',
        'avatar_media_id',
        'password',
        'theme',
        'role',
        'status',
        'banned_at',
        'ban_reason',
        'banned_by',
        'google_id',
        'email',
    ];

    protected $casts = [
        'banned_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];
}
