<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class EloquentUser extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
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

    /**
     * The factory class name doesn't match the default `Eloquent{Model}Factory`
     * convention Laravel would derive from this class's namespace, so it must
     * be resolved explicitly.
     */
    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
