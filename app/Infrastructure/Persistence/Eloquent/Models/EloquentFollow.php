<?php

// app/Infrastructure/Persistence/Eloquent/Models/EloquentFollow.php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\FollowFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EloquentFollow extends Model
{
    use HasFactory;
    use HasUlids;

    protected $table = 'follows';

    public $timestamps = false;

    protected $fillable = ['id', 'follower_id', 'followee_id', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * The factory class name doesn't match the default `Eloquent{Model}Factory`
     * convention Laravel would derive from this class's namespace, so it must
     * be resolved explicitly.
     */
    protected static function newFactory(): Factory
    {
        return FollowFactory::new();
    }
}
